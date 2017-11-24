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
        $looserUserId = null;

        switch ($battle->type_id) {
            case 3: // Combo
                $winnerUserId = self::compareBattleCombos($battle);
                $looserUserId = ($winnerUserId == $battle->user_id) ? $battle->opponent_user_id : $battle->user_id;
            break;

            case 4: // Combo-Sets
            case 5: // Workouts
                // TODO compare for combo-sets and workouts
            break;
        }

        return ['winner' => \App\User::get($winnerUserId), 'looser' => \App\User::get($looserUserId)];
    }

    // Compare combos type #3
    private static function compareBattleCombos($battle)
    {
        $comboPunches = self::getComboPunches($battle->plan_id);
        $roundPunches = [];
        $puchnes = []; // Full details of punches
        $speed = [];
        $speedOfCorrectPunches = [];
        $forceOfCorrectPunches = [];

        $sessions = \App\Sessions::with('rounds')->where('battle_id', $battle->id)->get();

        foreach($sessions as $session) {
            // Battle type combo will always have one round
            $round = $session->rounds{0};

            foreach ($round->punches as $punch) {
                $roundPunches[$session->user_id][] = $punch->hand.$punch->punch_type;
                
                $speed[$session->user_id][] = $punch->speed;

                // Full punch info, use to get speed/force of correct punches
                $puchnes[$session->user_id][] = $punch->toArray();
            }
        }

        $userMarks = $opponentMarks = 0;

        // Check for punches corrections
        foreach ($comboPunches as $key => $punch) {
            if ( @strpos($punch, $roundPunches[$battle->user_id][$key]) !== false ) {
                $speedOfCorrectPunches[$battle->user_id][] = $puchnes[$battle->user_id][$key]['speed'];
                $forceOfCorrectPunches[$battle->user_id][] = $puchnes[$battle->user_id][$key]['force'];
                $userMarks += 1;    
            }
            
            if ( @strpos($punch, $roundPunches[$battle->opponent_user_id][$key]) !== false ) {
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
        if ($userAvgSpeedOfCorrectPunches > 0 && $opponentAvgSpeedOfCorrectPunches > 0
            && $userAvgSpeedOfCorrectPunches == $opponentAvgSpeedOfCorrectPunches)
        {
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
        }
    }

    private static function getComboPunches($comboId)
    {
        $comboPunches = \App\ComboKeys::where('combo_id', $comboId)->pluck('punch_type_id')->toArray();
        
        foreach ($comboPunches as $i => $punch) {
            switch ($punch) {
                case 1: $comboPunches[$i] = 'LJ|RJ'; break; // LEFT JAB / RIGHT JAB
                case 2: $comboPunches[$i] = 'LS|RS'; break; // LEFT STRAIGHT / RIGHT STRAIGHT
                case 3: $comboPunches[$i] = 'LH'; break; // LEFT HOOK
                case 4: $comboPunches[$i] = 'RH'; break; // RIGHT HOOK
                case 5: $comboPunches[$i] = 'LU'; break; // LEFT UPPERCUT
                case 6: $comboPunches[$i] = 'RU'; break; // RIGHT UPPERCUT
                case 7: $comboPunches[$i] = 'LSH|RSH'; break; // LEFT SHOVEL HOOK / RIGHT SHOVEL HOOK
                
                // to keep format same hand + punch-type
                case 'DL': $comboPunches[$i] = 'LD'; break; // DUCK LEFT
                case 'DR': $comboPunches[$i] = 'RD'; break; // DUCK RIGHT
            }
        }

        return $comboPunches;
    }
}