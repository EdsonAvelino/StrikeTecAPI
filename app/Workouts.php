<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Workouts extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];
    public $timestamps = false;

    public function rounds()
    {
        return $this->hasMany('App\WorkoutRounds', 'workout_id', 'id');
    }

    public function getTagsAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $tags = \DB::table('workout_tags')->where('workout_id', $workoutId)->pluck('tag_id')->toArray();

        return $tags;
    }

    public function getFiltersAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $filters = \DB::table('workout_filters')->where('workout_id', $workoutId)->pluck('filter_id')->toArray();

        return $filters;
    }

    public function getRoundTimeAttribute($roundTimes)
    {
        $roundTimes = (int) $roundTimes;

        if (empty($roundTimes)) {
            return null;
        }

        $rounds = [];
        $roundTime = \DB::table('workout_metrics')->select('min', 'max', 'interval')->where('metric', 'round_time')->first();
        $count = 0;
        foreach (range($roundTime->min, $roundTime->max, $roundTime->interval) as $number) {
            $rounds[$number] = $count;
            $count = $count + 1;
        }
        return (int)$rounds[$roundTimes];
    }

    public function getRestTimeAttribute($restTimes)
    {
        $restTimes = (int) $restTimes;

        if (empty($restTimes)) {
            return null;
        }

        $rest = [];
        $restTime = \DB::table('workout_metrics')->select('min', 'max', 'interval')->where('metric', 'rest_time')->first();
        $count = 0;
        foreach (range($restTime->min, $restTime->max, $restTime->interval) as $number) {
            $rest[$number] = $count;
            $count = $count + 1;
        }
        return (int) $rest[$restTimes];
    }

    public function getPrepareTimeAttribute($preperationTime)
    {
        $preperationTime = (int) $preperationTime;

        if (empty($preperationTime)) {
            return null;
        }

        $prep = [];
        $prepTime = \DB::table('workout_metrics')->select('min', 'max', 'interval')->where('metric', 'prepare_time')->first();
        $count = 0;
        foreach (range($prepTime->min, $prepTime->max, $prepTime->interval) as $number) {
            $prep[$number] = $count;
            $count = $count + 1;
        }
        return (int) $prep[$preperationTime];
    }

    public function getWarningTimeAttribute($warningTime)
    {
        $warningTime = (int) $warningTime;

        if (empty($warningTime)) {
            return null;
        }

        $warning = [];
        $warningTimeData = \DB::table('workout_metrics')->select('min', 'max', 'interval')->where('metric', 'warning_time')->first();
        $count = 0;
        foreach (range($warningTimeData->min, $warningTimeData->max, $warningTimeData->interval) as $number) {
            $warning[$number] = $count;
            $count = $count + 1;
        }
        return (int) $warning[$warningTime];
    }

}
