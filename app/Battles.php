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
        * the man who has more correct punches will be winner
        * if correct is same, then server will calculate avg speed of all correct punches, and higher speed will be winner.
        * if avg speed is also same, then will determine with power of all correct punches
        * if both user don't have any correct punches, then will calculate with Speed of all punches
        */

        $battle = self::find($battleId);

        if (!$battle) return null;

        switch ($battle->type_id) {
            case 3:
                $winnerUserId = self::compareBattleCombos($battle);
            break;
        }

        return true;
    }

    // Compare combos type #3
    private static function compareBattleCombos($battle)
    {
        $comboPunches = self::getComboPunches($battle->plan_id);
        $punches = [];
        $speed = [];
        $force = [];

        $sessions = \App\Sessions::with('rounds')->where('battle_id', $battle->id)->get();

        foreach($sessions as $session) {
            $round = $session->rounds{0};

            foreach ($round->punches as $punch) {
                $punches[$session->user_id][] = $punch->hand.$punch->punch_type;
                $speed[$session->user_id][] = $punch->speed;
                $force[$session->user_id][] = $punch->force;
            }
        }

        $userMarks = $opponentMarks = 0;
        $userAvg = $opponentUser = 0;

        // Check for punches corrections
        foreach ($comboPunches as $key => $punch) {
            $userMarks += @(strpos($punch, $punches[$battle->user_id][$key]) !== false) ? 1 : 0;
            $opponentMarks += @(strpos($punch, $punches[$battle->opponent_user_id][$key]) !== false) ? 1 : 0;
        }

        // if correct is same, then server will calculate avg speed of all correct punches, and higher speed will be winner.
        if ($userMarks > 0 && $opponentMarks > 0 && $userMarks == $opponentMarks) {
            $userAvg = array_sum($speed[$battle->user_id]) / count($speed[$battle->user_id]);
            $opponentAvg = array_sum($speed[$battle->opponent_user_id]) / count($speed[$battle->opponent_user_id]);

            if ($userAvg > $opponentAvg) {
                return $battle->user_id;
            } else if ($opponentAvg > $userAvg) {
                return $battle->opponent_user_id;
            }
        }

        // if avg speed is also same, then will determine with power of all correct punches, higher power will be winner
        if ($userAvg > 0 && $opponentAvg > 0 && $userAvg == $opponentAvg) {
            $userMaxForce = max($force[$battle->user_id]);
            $opponentMaxForce = max($force[$battle->opponent_user_id]);

            if ($userMaxForce > $opponentMaxForce) {
                return $battle->user_id;
            } else if ($opponentMaxForce > $userMaxForce) {
                return $battle->opponent_user_id;
            }
        }

        // if both user don't have any correct punches, then will calculate with Speed of all punches
        // TODO
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