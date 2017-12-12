<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Battles extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'opponent_user_id',
        'plan_id',
        'type_id',
    ];

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function opponentUser()
    {
        return $this->hasOne('App\User', 'id', 'opponent_user_id');
    }

    public static function updateWinner($battleId)
    {
        $battle = self::find($battleId);

        $result = self::getResult($battleId);

        if (!is_null($result['winner']) && !is_null($result['loser'])) {
            $battle->winner_user_id = $result['winner']['id'];
            $battle->save();
        }
    }

    public static function getResult($battleId)
    {
        /*
         * SCENARIO
         * The man who has more correct punches will be winner
         * If correct is same, then server will calculate avg speed of all correct punches, and higher speed will be winner.
         * If avg speed is also same, then will determine with power of all correct punches
         * If both user don't have any correct punches, then will calculate with Speed of all punches
         */

        $battle = self::find($battleId);

        // When battle not found
        if (!$battle)
            return null;

        // Check battle is finished from both side
        if (!$battle->user_finished && !$battle->opponent_finished)
            return null;

        $winnerUserId = null;
        $loserUserId = null;

        switch ($battle->type_id) {
            case 3: // Combo
                $winnerUserId = @self::compareBattleCombos($battle);
                $loserUserId = ( $winnerUserId == $battle->user_id) ? $battle->opponent_user_id :
                        ( ($winnerUserId == $battle->opponent_user_id) ? $battle->user_id : null );
                break;

            case 4: // Combo-Sets
                $winnerUserId = @self::compareComboSets($battle);
                $loserUserId = ( $winnerUserId == $battle->user_id) ? $battle->opponent_user_id :
                        ( ($winnerUserId == $battle->opponent_user_id) ? $battle->user_id : null );
                break;

            case 5: // Workouts
                // TODO compare for combo-sets and workouts
                break;
        }

        $winner = $loser = null;

        if ($winnerUserId && $loserUserId) {
            // Winner
            $winner = \App\User::get($winnerUserId)->toArray();

            $_session = \App\Sessions::where('battle_id', $battle->id)->where('user_id', $winnerUserId)->first();
            $winner['avg_speed'] = $_session->avg_speed;
            $winner['avg_force'] = $_session->avg_force;
            $winner['max_speed'] = $_session->max_speed;
            $winner['max_force'] = $_session->max_force;
            $winner['best_time'] = $_session->best_time;
            $winner['punches_count'] = $_session->punches_count;

            // loser
            $loser = \App\User::get($loserUserId)->toArray();

            $_session = \App\Sessions::where('battle_id', $battle->id)->where('user_id', $loserUserId)->first();
            $loser['avg_speed'] = $_session->avg_speed;
            $loser['avg_force'] = $_session->avg_force;
            $loser['max_speed'] = $_session->max_speed;
            $loser['max_force'] = $_session->max_force;
            $loser['best_time'] = $_session->best_time;
            $loser['punches_count'] = $_session->punches_count;
        }

        return ['winner' => $winner, 'loser' => $loser];
    }

    // Compare combos type #3
    private static function compareBattleCombos($battle)
    {
        $comboPunches = self::getComboPunches($battle->plan_id);

        return self::doComparison($comboPunches, $battle);
    }

    // Compare combo-sets #4
    private static function compareComboSets($battle)
    {
        $comboSet = \App\ComboSets::find($battle->plan_id);
        $comboPunches = [];

        foreach ($comboSet->combos as $combo) {
            $comboPunches = array_merge($comboPunches, self::getComboPunches($combo->combo_id));
        }

        return self::doComparison($comboPunches, $battle);
    }

    // Compare workouts #5
    private static function compareWorkouts($battle)
    {
        
    }

    public static function getComboPunches($comboId)
    {
        $comboPunches = \App\ComboKeys::where('combo_id', $comboId)->pluck('punch_type_id')->toArray();

        foreach ($comboPunches as $i => $punch) {
            switch ($punch) {
                case 1: $comboPunches[$i] = 'LJ|RJ';
                    break; // LEFT JAB / RIGHT JAB
                case 2: $comboPunches[$i] = 'LS|RS';
                    break; // LEFT STRAIGHT / RIGHT STRAIGHT
                case 3: $comboPunches[$i] = 'LH';
                    break; // LEFT HOOK
                case 4: $comboPunches[$i] = 'RH';
                    break; // RIGHT HOOK
                case 5: $comboPunches[$i] = 'LU';
                    break; // LEFT UPPERCUT
                case 6: $comboPunches[$i] = 'RU';
                    break; // RIGHT UPPERCUT
                case 7: $comboPunches[$i] = 'LSH|RSH';
                    break; // LEFT SHOVEL HOOK / RIGHT SHOVEL HOOK
                // to keep format same hand + punch-type
                case 'DL': $comboPunches[$i] = 'LD';
                    break; // DUCK LEFT
                case 'DR': $comboPunches[$i] = 'RD';
                    break; // DUCK RIGHT
            }
        }

        return $comboPunches;
    }

    private static function doComparison($comboPunches, $battle)
    {
        $roundPunches = [];
        $puchnes = []; // Full details of punches
        $speed = [];
        $speedOfCorrectPunches = [];
        $forceOfCorrectPunches = [];

        $sessions = \App\Sessions::with('rounds')->where('battle_id', $battle->id)->get();

        // In case of no sessoins found for battle (would be very rare case)
        if ($sessions->isEmpty())
            return null;

        foreach ($sessions as $session) {
            // Battle type combo and combo-set will always have one round
            $round = $session->rounds()->first();

            foreach ($_punches = $round->punches as $punch) {
                $roundPunches[$session->user_id][] = $punch->hand . $punch->punch_type;

                $speed[$session->user_id][] = $punch->speed;

                // Full punch info, use to get speed/force of correct punches
                $puchnes[$session->user_id][] = $punch->toArray();
            }
        }

        $userMarks = $opponentMarks = 0;
        $userAvgSpeedOfCorrectPunches = $opponentAvgSpeedOfCorrectPunches = 0;

        // Check for punches corrections
        foreach ($comboPunches as $key => $punch) {
            if (@strpos($punch, $roundPunches[$battle->user_id][$key]) !== false) {
                $speedOfCorrectPunches[$battle->user_id][] = $puchnes[$battle->user_id][$key]['speed'];
                $forceOfCorrectPunches[$battle->user_id][] = $puchnes[$battle->user_id][$key]['force'];
                $userMarks += 1;
            }

            if (@strpos($punch, $roundPunches[$battle->opponent_user_id][$key]) !== false) {
                $speedOfCorrectPunches[$battle->opponent_user_id][] = $puchnes[$battle->opponent_user_id][$key]['speed'];
                $forceOfCorrectPunches[$battle->opponent_user_id][] = $puchnes[$battle->opponent_user_id][$key]['force'];
                $opponentMarks += 1;
            }
        }

        // If correct is same, then server will calculate avg speed of all correct punches, and higher speed will be winner.
        if ($userMarks > 0 && $opponentMarks > 0 && $userMarks == $opponentMarks) {
            $userAvgSpeedOfCorrectPunches = (float) array_sum($speedOfCorrectPunches[$battle->user_id]) / (float) count($speedOfCorrectPunches[$battle->user_id]);

            $opponentAvgSpeedOfCorrectPunches = (float) array_sum($speedOfCorrectPunches[$battle->opponent_user_id]) / (float) $speedOfCorrectPunches[$battle->opponent_user_id];

            if ($userAvgSpeedOfCorrectPunches > $opponentAvgSpeedOfCorrectPunches) {
                return $battle->user_id;
            } else if ($opponentAvgSpeedOfCorrectPunches > $userAvgSpeedOfCorrectPunches) {
                return $battle->opponent_user_id;
            }
        }

        // If avg speed is also same, then will determine with power of all correct punches, higher power will be winner
        if ($userAvgSpeedOfCorrectPunches > 0 && $opponentAvgSpeedOfCorrectPunches > 0 && $userAvgSpeedOfCorrectPunches == $opponentAvgSpeedOfCorrectPunches) {
            $userMaxForce = max($forceOfCorrectPunches[$battle->user_id]);
            $opponentMaxForce = max($forceOfCorrectPunches[$battle->opponent_user_id]);

            if ($userMaxForce > $opponentMaxForce) {
                return $battle->user_id;
            } else if ($opponentMaxForce > $userMaxForce) {
                return $battle->opponent_user_id;
            }
        }

        // If both users don't have any correct punches, then will calculate with Speed of all punches, fastet(lowest) will be winner
        if (!$userMarks && !$opponentMarks) {
            $userSpeed = min($speed[$battle->user_id]);
            $opponentSpeed = min($speed[$battle->opponent_user_id]);

            if ($userSpeed < $opponentSpeed) {
                return $battle->user_id;
            } else if ($opponentSpeed < $userAvg) {
                return $battle->opponent_user_id;
            }
        } elseif ($userMarks > $opponentMarks) {
            return $battle->user_id;
        } elseif ($opponentMarks > $userMarks) {
            return $battle->opponent_user_id;
        }
    }

    // Finished battles of user
    public static function getFinishedBattles($userId, $days = 0, $offset = 0, $limit = 20)
    {
        $_finishedBattles = self::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id', 'user_finished_at', 'opponent_finished_at', 'user_shared','opponent_shared')
                        ->where(function ($query)use($userId) {
                            $query->where(['user_id' => $userId])->orWhere(['opponent_user_id' => $userId]);
                        })
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->orderBy('battles.updated_at', 'desc');
        
        if ($days > 1) {
            $_finishedBattles->whereRaw('created_at >= DATE_FORMAT(NOW(), "%Y-%m-%d 00:00:00") - INTERVAL '.$days.' DAY');
        }

        $finishedBattles = $_finishedBattles->offset($offset)->limit($limit)->get();

        $data = [];
        $lost = $won = 0;

        foreach ($finishedBattles as $battle) {
            $battleResult = self::getResult($battle->battle_id);

            if (!$battleResult['winner'] || !$battleResult['loser']) {
                continue;
            } else {
                $share['battle_id'] = $battle->battle_id;

                $share['shared'] = filter_var($battle->user_shared, FILTER_VALIDATE_BOOLEAN);

                if ($userId == $battle->opponent_user_id) {
                    $share['shared'] = filter_var($battle->opponent_shared, FILTER_VALIDATE_BOOLEAN);
                }

                $data[] = array_merge($share, $battleResult);
            }
        }

        $won = \App\Battles::where('winner_user_id', $userId)->count();
        $lost = \App\Battles::where(function($query) use($userId) {
                                    $query->where('user_id', $userId)->orWhere('opponent_user_id', $userId);
                                })->where('winner_user_id', '!=', $userId)->count();

        return ['lost' => $lost, 'won' => $won, 'finished' => $data];
    }

    public static function getBeltCount($userId)
    {
        $finishedBattles = self::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id', 'user_finished_at', 'opponent_finished_at', 'user_shared', 'opponent_shared')
                        ->where(function ($query)use($userId) {
                            $query->where(['user_id' => $userId])->orWhere(['opponent_user_id' => $userId]);
                        })
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->orderBy('battles.updated_at', 'desc')
                        ->get();

        $winCount = $beltCount = 0; 
        foreach ($finishedBattles as $battle) {
            $battleResult = self::getResult($battle->battle_id);

            if (!$battleResult['winner'] || !$battleResult['loser']) {
                continue;
            } else {
                if ($battle->winner_user_id == $userId) {
                    $winCount++;
                } else {
                    $winCount = 0;
                }
            }
            if($winCount == 5)
            {
                $winCount = 0;
                $beltCount++;
            }
        }

        return $beltCount;
    }

}
