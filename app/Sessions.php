<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sessions extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'battle_id',
        'game_id',
        'type_id',
        'start_time',
        'end_time',
        'plan_id',
        'avg_speed',
        'avg_force',
        'punches_count',
        'max_force',
        'max_speed',
        'best_time',
    ];
    protected $dateFormat = 'Y-m-d\TH:i:s.u';

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function rounds()
    {
        return $this->hasMany('App\SessionRounds', 'session_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $timestamp = $model->start_time / 1000;

            $micro = sprintf("%06d", ($timestamp - floor($timestamp)) * 1000000);
            $d = new \DateTime(date('Y-m-d H:i:s.' . $micro, $timestamp));

            $model->created_at = $d->format("Y-m-d H:i:s.u");
        });
    }

    protected function asDateTime($value)
    {
        try {
            return parent::asDateTime($value);
        } catch (\InvalidArgumentException $e) {
            return parent::asDateTime(new \DateTimeImmutable($value));
        }
    }

    public function setBattleIdAttribute($battleId)
    {
        $battleId = (int) $battleId;
        $this->attributes['battle_id'] = ($battleId > 0) ? $battleId : null;
    }

    public function setGameIdAttribute($gameId)
    {
        $gameId = (int) $gameId;
        $this->attributes['game_id'] = ($gameId > 0) ? $gameId : null;
    }

    public function getSharedAttribute($shared)
    {
        $shared = filter_var($shared, FILTER_VALIDATE_BOOLEAN);
        return ($shared) ? 'true' : 'false';
    }

    public static function getPunchCount()
    {
        //$createdDate = date('Y-m-d');
        if(strtolower(date('l'))=='monday'){
            $createdDate = date('Y-m-d');
        }
        else{
            $createdDate = date('Y-m-d',strtotime('Previous Monday'));
        }
        $punchesCount = self::select(\DB::raw('SUM(punches_count) as punch_count'))->where('user_id', \Auth::user()->id)
                        ->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->where('created_at', '>', $createdDate)->first();
        return $punchesCount->punch_count;
    }

    public static function getMostPowerfulPunchAndSpeed($sessonId)
    {
        $punchCount = self::select('max_force', 'max_speed')
                        ->where('user_id', \Auth::user()->id)
                        ->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->where('id', $sessonId)->first();
        return $punchCount;
    }

    public static function getUserParticpation($userId, $perviousMonday)
    {
        $userParticpation = self::where('user_id', $userId)
                        ->where('start_time', '>', ($perviousMonday * 1000))
                        ->where(function($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->count();
        return $userParticpation;
    }

    public static function getSpeedDemon($trainingCount, $userId, $perviousMonday)
    {
        $speedDemon = self::where('user_id', $userId)
                ->where(function($query) {
                    $query->whereNull('battle_id')->orWhere('battle_id', '0');
                })
                ->where('avg_speed', '>', $trainingCount)
                ->where('start_time', '>', ($perviousMonday * 1000))
                ->count();

        return $speedDemon;
    }

    public static function getStrongMen($force, $userId, $perviousMonday)
    {
        $returnData = self::where('user_id', $userId)
                ->where(function($query) {
                    $query->whereNull('battle_id')->orWhere('battle_id', '0');
                })
                ->where('avg_force', '>', $force)
                ->where('start_time', '>', ($perviousMonday * 1000))
                ->count();
        return $returnData;
    }

    public static function ironFirst($userId, $perviousMonday)
    {
        $ironFirst = self::select(\DB::raw('MAX(max_force) as max_force'))
                ->where('user_id', $userId)
                ->where(function ($query) {
                    $query->whereNull('battle_id')->orWhere('battle_id', '0');
                })->where('start_time', '>', ($perviousMonday * 1000))
                ->first();
        return $ironFirst->max_force;
    }

    public static function getAccuracy($userId,$perviousMonday)
    {
        $sessionsData = \App\Sessions::with('rounds')
        				->where('user_id', $userId)
                        ->where('start_time', '>', ($perviousMonday * 1000))
                        ->where(function($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->get();

         //\Log::info(print_r($sessionsData,true));

        // In case of no sessoins found for battle (would be very rare case)
        if ($sessionsData->isEmpty())
            return null;
        
        $finalData = 0;
        foreach ($sessionsData as $sessions) {
        	if ($sessions->type_id == 3) {
                $data = @self::compareSessionBattleCombos($sessions);
                if (is_int($data)) {
                $finalData += $data;
                }
            }
        }
        
        return $finalData;
    }

    private static function doSessionComparison($comboPunches, $session)
    {
        $roundPunches = [];
        $puchnes = []; // Full details of punches
        $speed = [];
        $speedOfCorrectPunches = [];
        $forceOfCorrectPunches = [];

        $sessions = \App\Sessions::with('rounds')->where('id', $session->id)->get();

        // In case of no sessoins found for battle (would be very rare case)
        if ($sessions->isEmpty())
            return null;
        $userMarks = 0;
        foreach ($sessions as $session) {
            // Battle type combo and combo-set will always have one round
            $rounds = $session->rounds()->get();
            foreach ($rounds as $round) {
                $roundPunches[$session->user_id] = [];
                $section_index = 0;
                $combo_cnt = count($comboPunches);
                $punch_cnt = count($round->punches);
                $punch_index = 0;
                for ($punch_index = 0; $punch_index < $punch_cnt; $punch_index += $combo_cnt) {
                    $isMatch = true;
                    foreach ($comboPunches as $key => $combo) {
                        $punch = $round->punches[$punch_index + $key];
                        $strPunch = $punch->hand . $punch->punch_type;
                        if (@strpos($combo, $strPunch) !== false) {
                        } else {
                            $isMatch = false;
                        }
                    }
                    if ($isMatch) {
                        $userMarks += 1;
                    }
                }
                // foreach ($_punches = $round->punches as $key => $punch) {
                //     $roundPunch = $punch->hand . $punch->punch_type;
                //     var_dump(json_encode($roundPunch));
                //     var_dump(json_encode($comboPunches));
                //     $section_index = $key / $section_len;
                //     if (@strpos($comboPunches[$section_index], $roundPunch) !== false) {
                //         $userMarks += 1;
                //     }
                // }
            }
        }
        return $userMarks;
    }

    // Compare combos type #3
    private static function compareSessionBattleCombos($session)
    {
        $comboPunches = Battles::getComboPunches($session->plan_id);

        return self::doSessionComparison($comboPunches, $session);
    }

    // Compare combo-sets #4
    private static function compareSessionComboSets($session)
    {
        $comboSet = \App\ComboSets::find($session->plan_id);
        $comboPunches = [];

        foreach ($comboSet->combos as $combo) {
            $comboPunches = array_merge($comboPunches, Battles::getComboPunches($combo->combo_id));
        }

        return self::doSessionComparison($comboPunches, $session);
    }

    // Compare workouts #5
    private static function compareSessionWorkouts($session)
    {
        
    }

    public static function getMissingPunches($sessions)
    {
        $data = [];

        switch ($sessions->type_id) {
            case 3: // Combo
                $data = @self::comparePunchCombos($sessions);
                break;

            case 4: // Combo-Sets
                $data = @self::comparePunchComboSets($sessions);
                break;

            case 5: // Workouts
                // TODO compare for combo-sets and workouts
                break;
        }
        return $data;
    }

    private static function doPunchComparison($comboPunches, $session, $comboPunchType)
    {
        $roundPunches = [];
        $missingPunch = $missing = [];
        $sessions = self::with('rounds')->where('id', $session->id)->get();

        // In case of no sessoins found for battle (would be very rare case)
        if ($sessions->isEmpty())
            return null;
        $userMarks = 0;
        // Battle type combo and combo-set will always have one round
        $rounds = $session->rounds()->get();
        foreach ($rounds as $round) {
            $roundPunches[$session->user_id] = [];
            foreach ($_punches = $round->punches as $key => $punch) {
                $roundPunch = $punch->hand . $punch->punch_type;
                if (@strpos($comboPunches[$key], $roundPunch) == false) {
                    $missingPunch[$comboPunchType[$key]][] = 1;
                }
            }
        }
        foreach ($missingPunch as $key => $data) {
            $missing[$key] = array_sum($data);
        }
        return $missing;
    }

    // Compare combos type #3
    private static function comparePunchCombos($session)
    {
        $comboPunches = Battles::getComboPunches($session->plan_id);
        $comboPunchesType = self::getPunches($session->plan_id);
        return self::doPunchComparison($comboPunches, $session, $comboPunchesType);
    }

    // Compare combo-sets #4
    private static function comparePunchComboSets($session)
    {
        $comboSet = \App\ComboSets::find($session->plan_id);
        $comboPunches = [];
        $comboPunchesTypes = [];

        foreach ($comboSet->combos as $combo) {
            $comboPunches = array_merge($comboPunches, Battles::getComboPunches($combo->combo_id));
            $comboPunchesTypes = array_merge($comboPunchesTypes, self::getPunches($combo->combo_id));
        }

        return self::doPunchComparison($comboPunches, $session, $comboPunchesTypes);
    }

    public static function getPunches($comboId)
    {
        $comboPunches = \App\ComboKeys::where('combo_id', $comboId)->pluck('punch_type_id')->toArray();

        foreach ($comboPunches as $i => $punch) {
            switch ($punch) {
                case 1: $comboPunches[$i] = 'J';
                    break; // LEFT JAB / RIGHT JAB
                case 2: $comboPunches[$i] = 'S';
                    break; // LEFT STRAIGHT / RIGHT STRAIGHT
                case 3: $comboPunches[$i] = 'LH';
                    break; // LEFT HOOK
                case 4: $comboPunches[$i] = 'RH';
                    break; // RIGHT HOOK
                case 5: $comboPunches[$i] = 'LU';
                    break; // LEFT UPPERCUT
                case 6: $comboPunches[$i] = 'RU';
                    break; // RIGHT UPPERCUT
                case 7: $comboPunches[$i] = 'SH';
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

}
