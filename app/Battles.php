<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Battles extends Model
{
    protected $fillable = [
        'user_id',
        'opponent_user_id',
        'plan_id',
        'type_id',
    ];

    private static $result = [];

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

        $result = self::getResultForWinner($battleId);

        if (!is_null($result['winnerUserId'])) {
            $battle->winner_user_id = $result['winnerUserId'];
            $battle->save();
        }
    }

    public static function getResultForWinner($battleId)
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
        if (!$battle->user_finished || !$battle->opponent_finished)
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
            return ['winnerUserId' => $winnerUserId, 'loserUserId' => $loserUserId];
        }
        else{
            return false;
        }
        
    }

    public static function getResultForBattleDetails($battleId)
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
        if (!$battle->user_finished || !$battle->opponent_finished)
            return null;

        $winner = $loser = null;

        $winnerUserId = $battle->winner_user_id;

        if($winnerUserId==$battle->user_id){
            $loserUserId = $battle->opponent_user_id;
        }
        else{
            $loserUserId = $battle->userId;
        }

        if ($winnerUserId && $loserUserId) {

            // Winner
            $winner = \App\User::get($winnerUserId)->toArray();

            $_session = \App\Sessions::where('battle_id', $battle->id)->where('user_id', $winnerUserId)->first();
            $winner['avg_speed'] = (int) self::$result[$winnerUserId]['avg_speed'];
            $winner['avg_force'] = (int) self::$result[$winnerUserId]['avg_force'];
            $winner['max_speed'] = (float) self::$result[$winnerUserId]['max_speed'];
            $winner['max_force'] = (float) self::$result[$winnerUserId]['max_force'];
            $winner['best_time'] = $_session->best_time;
            $winner['punches_count'] = (int) self::$result[$winnerUserId]['punches_count'];

            // loser
            $loser = \App\User::get($loserUserId)->toArray();

            $_session = \App\Sessions::where('battle_id', $battle->id)->where('user_id', $loserUserId)->first();
            $loser['avg_speed'] = (int) self::$result[$loserUserId]['avg_speed'];
            $loser['avg_force'] = (int) self::$result[$loserUserId]['avg_force'];
            $loser['max_speed'] = (float) self::$result[$loserUserId]['max_speed'];
            $loser['max_force'] = (float) self::$result[$loserUserId]['max_force'];
            $loser['best_time'] = $_session->best_time;
            $loser['punches_count'] = (int) self::$result[$loserUserId]['punches_count'];
        }

        return ['winner' => $winner, 'loser' => $loser];
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
        if (!$battle->user_finished || !$battle->opponent_finished)
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
            $winner['avg_speed'] = (int) self::$result[$winnerUserId]['avg_speed'];
            $winner['avg_force'] = (int) self::$result[$winnerUserId]['avg_force'];
            $winner['max_speed'] = (float) self::$result[$winnerUserId]['max_speed'];
            $winner['max_force'] = (float) self::$result[$winnerUserId]['max_force'];
            $winner['best_time'] = $_session->best_time;
            $winner['punches_count'] = (int) self::$result[$winnerUserId]['punches_count'];

            // loser
            $loser = \App\User::get($loserUserId)->toArray();

            $_session = \App\Sessions::where('battle_id', $battle->id)->where('user_id', $loserUserId)->first();
            $loser['avg_speed'] = (int) self::$result[$loserUserId]['avg_speed'];
            $loser['avg_force'] = (int) self::$result[$loserUserId]['avg_force'];
            $loser['max_speed'] = (float) self::$result[$loserUserId]['max_speed'];
            $loser['max_force'] = (float) self::$result[$loserUserId]['max_force'];
            $loser['best_time'] = $_session->best_time;
            $loser['punches_count'] = (int) self::$result[$loserUserId]['punches_count'];
        }

        return ['winner' => $winner, 'loser' => $loser];
    }

    public static function getResultForFinishedBattle($battleId)
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
        if (!$battle->user_finished || !$battle->opponent_finished)
            return null;

        $winnerUserId = null;
        $loserUserId = null;

        switch ($battle->type_id) {
            case 3: // Combo
                $winnerUserId = $battle->winner_user_id;
                $loserUserId = ( $winnerUserId == $battle->user_id) ? $battle->opponent_user_id :
                        ( ($winnerUserId == $battle->opponent_user_id) ? $battle->user_id : null );
                break;

            case 4: // Combo-Sets
                $winnerUserId = $battle->winner_user_id;
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

            //$_session = \App\Sessions::where('battle_id', $battle->id)->where('user_id', $winnerUserId)->first();
            $winner['avg_speed'] = 0;
            $winner['avg_force'] = 0;
            $winner['max_speed'] = 0;
            $winner['max_force'] = 0;
            $winner['best_time'] = 0;
            $winner['punches_count'] = 0;

            // loser
            $loser = \App\User::get($loserUserId)->toArray();

            //$_session = \App\Sessions::where('battle_id', $battle->id)->where('user_id', $loserUserId)->first();
            $loser['avg_speed'] = 0;
            $loser['avg_force'] = 0;
            $loser['max_speed'] = 0;
            $loser['max_force'] = 0;
            $loser['best_time'] = 0;
            $loser['punches_count'] = 0;
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
                case 2: $comboPunches[$i] = 'LC|RC';
                    break; // LEFT CROSS / RIGHT CROSS
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
                case 8: $comboPunches[$i] = 'RO';
                    break; // RIGHT OVERHAND
                    // We don't have LEFT OVERHAND
                // to keep format same hand + punch-type
                case 'DL': $comboPunches[$i] = 'LD';
                    break; // DUCK LEFT
                case 'DR': $comboPunches[$i] = 'RD';
                    break; // DUCK RIGHT
                case 'P': $comboPunches[$i] = 'LP|RP';
                    break; // LEFT / RIGHT PARRY PUNCH 
                case 'RL': $comboPunches[$i] = 'LR';
                    break; // ROLL LEFT
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

        // Defining first
        $resultData = ['avg_speed' => 0, 'avg_force' => 0, 'max_speed' => 0, 'max_force' => 0, 'punches_count' => 0];
        self::$result[$battle->user_id] = $resultData;
        self::$result[$battle->opponent_user_id] = $resultData;

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

        // Total correct punches, currently we're considering total-correct-punches as marks
        self::$result[$battle->user_id]['punches_count'] = $userMarks;
        self::$result[$battle->opponent_user_id]['punches_count'] = $opponentMarks;

        // Avg Speed of correct punches
        $userAvgSpeedOfCorrectPunches = (float) array_sum($speedOfCorrectPunches[$battle->user_id]) / (float) count($speedOfCorrectPunches[$battle->user_id]);

        $opponentAvgSpeedOfCorrectPunches = (float) array_sum($speedOfCorrectPunches[$battle->opponent_user_id]) / (float) count($speedOfCorrectPunches[$battle->opponent_user_id]);

        self::$result[$battle->user_id]['avg_speed'] = $userAvgSpeedOfCorrectPunches;
        self::$result[$battle->opponent_user_id]['avg_speed'] = $opponentAvgSpeedOfCorrectPunches;

        // Avg Force of correct punches
        $userAvgForceOfCorrectPunches = (float) array_sum($forceOfCorrectPunches[$battle->user_id]) / (float) count($forceOfCorrectPunches[$battle->user_id]);

        $opponentAvgForceOfCorrectPunches = (float) array_sum($forceOfCorrectPunches[$battle->opponent_user_id]) / (float) count($forceOfCorrectPunches[$battle->opponent_user_id]);

        self::$result[$battle->user_id]['avg_force'] = $userAvgForceOfCorrectPunches;
        self::$result[$battle->opponent_user_id]['avg_force'] = $opponentAvgForceOfCorrectPunches;

        // Max Speed of correct punches
        $userMaxSpeed = max($speedOfCorrectPunches[$battle->user_id]);
        $opponentMaxSpeed = max($speedOfCorrectPunches[$battle->opponent_user_id]);

        self::$result[$battle->user_id]['max_speed'] = $userMaxSpeed;
        self::$result[$battle->opponent_user_id]['max_speed'] = $opponentMaxSpeed;

        // Max Force of correct punches
        $userMaxForce = max($forceOfCorrectPunches[$battle->user_id]);
        $opponentMaxForce = max($forceOfCorrectPunches[$battle->opponent_user_id]);

        self::$result[$battle->user_id]['max_force'] = $userMaxForce;
        self::$result[$battle->opponent_user_id]['max_force'] = $opponentMaxForce;

        // If correct is same, then server will calculate avg speed of all correct punches, and higher speed will be winner.
        if ($userMarks > 0 && $opponentMarks > 0 && $userMarks == $opponentMarks) {
            if ($userAvgSpeedOfCorrectPunches > $opponentAvgSpeedOfCorrectPunches) {
                return $battle->user_id;
            } elseif ($opponentAvgSpeedOfCorrectPunches > $userAvgSpeedOfCorrectPunches) {
                return $battle->opponent_user_id;
            }
        }

        // If avg speed is also same, then will determine with power of all correct punches, higher power will be winner
        if ($userAvgSpeedOfCorrectPunches > 0 && $opponentAvgSpeedOfCorrectPunches > 0 && $userAvgSpeedOfCorrectPunches == $opponentAvgSpeedOfCorrectPunches)
        {
            if ($userMaxForce > $opponentMaxForce) {
                return $battle->user_id;
            } elseif ($opponentMaxForce > $userMaxForce) {
                return $battle->opponent_user_id;
            }
        }

        // If both users don't have any correct punches, then will calculate with Speed of all punches, fastet(lowest) will be winner
        if (!$userMarks && !$opponentMarks) {
            $userSpeed = min($speed[$battle->user_id]);
            $opponentSpeed = min($speed[$battle->opponent_user_id]);

            if ($userSpeed < $opponentSpeed) {
                return $battle->user_id;
            } elseif ($opponentSpeed < $userAvg) {
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
        $_finishedBattles = self::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id', 'user_finished_at', 'opponent_finished_at', 'user_shared', 'opponent_shared')
                ->where(function ($query)use($userId) {
                    $query->where(['user_id' => $userId])->orWhere(['opponent_user_id' => $userId]);
                })
                ->where(['opponent_finished' => TRUE])
                ->where(['user_finished' => TRUE])
                ->orderBy('battles.updated_at', 'desc');

        if ($days > 1) {
            $_finishedBattles->whereRaw('created_at >= DATE_FORMAT(NOW(), "%Y-%m-%d 00:00:00") - INTERVAL ' . $days . ' DAY');
        }

        $finishedBattles = $_finishedBattles->offset($offset)->limit($limit)->get();

        $data = [];
        $lost = $won = 0;

        foreach ($finishedBattles as $battle) {
            $battleResult = self::getResultForFinishedBattle($battle->battle_id);

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
            

            if ($winCount == 5) {
                $winCount = 0;
                $beltCount++;
            }
        }
        
        return $beltCount;
    }

    public static function getChampian($battleId)
    {
        $champion = 0;
        $battle = self::where('user_id', \Auth::user()->id)->Where('id', $battleId)->where('winner_user_id', \Auth::user()->id)->get();
        if ($battle) {
            $finishedBattle = self::getFinishedBattles(\Auth::user()->id, $days = 0, $offset = 0, $limit = 5);
            if ($finishedBattle['won'] == 5)
                $champion = 1;
        }
        return $champion;
    }

}
