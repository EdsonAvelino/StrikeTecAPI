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

    public function getSharedAttribute($shared)
    {
        $shared = filter_var($shared, FILTER_VALIDATE_BOOLEAN);
        return ($shared) ? 'true' : 'false';
    }

    public static function getPunchCount()
    {
        $createdDate = date('Y-m-d');
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

    public static function getUserParticpation()
    {
        $currentWeekMonday = strtotime("monday this week");
        $lastWeekMonday = strtotime('Monday last week');
        $userParticpation = self::where('user_id', \Auth::user()->id)
                        ->where('start_time', '<', ($currentWeekMonday * 1000))
                        ->where('start_time', '>', ($lastWeekMonday * 1000))
                        ->where(function($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->count();
        return $userParticpation;
    }

    public static function getSpeedDemon($trainingCount)
    {
        $currentWeekMonday = strtotime("monday this week");
        $lastWeekMonday = strtotime('Monday last week');
        $speedDemon = self::where('user_id', \Auth::user()->id)
                ->where(function($query) {
                    $query->whereNull('battle_id')->orWhere('battle_id', '0');
                })
                ->where('avg_speed', '>', $trainingCount)
                ->where('start_time', '<', ($currentWeekMonday * 1000))
                ->where('start_time', '>', ($lastWeekMonday * 1000))
                ->count();

        return $speedDemon;
    }

    public static function getStrongMen($force)
    {
        $currentWeekMonday = strtotime("monday this week");
        $lastWeekMonday = strtotime('Monday last week');
        $returnData = self::where('user_id', \Auth::user()->id)
                ->where(function($query) {
                    $query->whereNull('battle_id')->orWhere('battle_id', '0');
                })
                ->where('avg_force', '>', $force)
                ->where('start_time', '<', ($currentWeekMonday * 1000))
                ->where('start_time', '>', ($lastWeekMonday * 1000))
                ->count();
        return $returnData;
    }

    public static function ironFirst()
    {
        $currentWeekMonday = strtotime("monday this week");
        $lastWeekMonday = strtotime('Monday last week');
        $ironFirst = self::select(\DB::raw('MAX(max_force) as max_force'))
                ->where('user_id', \Auth::user()->id)
                ->where(function ($query) {
                    $query->whereNull('battle_id')->orWhere('battle_id', '0');
                })->where('start_time', '<', ($currentWeekMonday * 1000))
                ->where('start_time', '>', ($lastWeekMonday * 1000))
                ->first();
        return $ironFirst->max_force;
    }

    public static function getAccuracy()
    {
        $currentWeekMonday = strtotime("monday this week");
        $lastWeekMonday = strtotime('Monday last week');
        $sessionsData = \App\Sessions::with('rounds')
                        ->where('start_time', '<', ($currentWeekMonday * 1000))
                        ->where('start_time', '>', ($lastWeekMonday * 1000))
                        ->where(function($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->get();
        // In case of no sessoins found for battle (would be very rare case)
        if ($sessionsData->isEmpty())
            return null;
        foreach ($sessionsData as $sessions) {
            switch ($sessions->type_id) {
                case 3: // Combo
                    $data = @self::compareSessionBattleCombos($sessions);
                    break;

                case 4: // Combo-Sets
                    $data = @self::compareSessionComboSets($sessions);
                    break;

                case 5: // Workouts
                    // TODO compare for combo-sets and workouts
                    break;
            }
        }
//        print_r($data);die;
        return $data;
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
                foreach ($_punches = $round->punches as $key => $punch) {
                    $roundPunch = $punch->hand . $punch->punch_type;
                    if (@strpos($comboPunches[$key], $roundPunch) !== false) {
                        $userMarks += 1;
                    }
                }
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
            $comboPunches = array_merge($comboPunches, self::getComboPunches($combo->combo_id));
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

    private static function doPunchComparison($comboPunches, $session)
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
                    $missingPunch[$punch->punch_type][] = 1;
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
        return self::doPunchComparison($comboPunches, $session);
    }

    // Compare combo-sets #4
    private static function comparePunchComboSets($session)
    {
        $comboSet = \App\ComboSets::find($session->plan_id);
        $comboPunches = [];

        foreach ($comboSet->combos as $combo) {
            $comboPunches = array_merge($comboPunches, Battles::getComboPunches($combo->combo_id));
        }

        return self::doPunchComparison($comboPunches, $session);
    }

}
