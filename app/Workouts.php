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

    public function getRoundTimeAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $rounds = [];
        $roundTime = \DB::table('workout_metrics')->where('workout_id', $workoutId)->select('min', 'max', 'interval')->where('metric', 'round_time')->first();
        $count = 0;
        foreach (range($roundTime->min, $roundTime->max, $roundTime->interval) as $number) {
            $rounds[$number] = $count;
            $count = $count + 1;
        }
        return $rounds;
    }

    public function getRestTimeAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $rest = [];
        $restTime = \DB::table('workout_metrics')->where('workout_id', $workoutId)->select('min', 'max', 'interval')->where('metric', 'rest_time')->first();
        $count = 0;
        foreach (range($restTime->min, $restTime->max, $restTime->interval) as $number) {
            $rest[$number] = $count;
            $count = $count + 1;
        }
        return $rest;
    }

    public function getPrepareTimeAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $prep = [];
        $prepTime = \DB::table('workout_metrics')->where('workout_id', $workoutId)->select('min', 'max', 'interval')->where('metric', 'prepare_time')->first();
        $count = 0;
        foreach (range($prepTime->min, $prepTime->max, $prepTime->interval) as $number) {
            $prep[$number] = $count;
            $count = $count + 1;
        }
        return $prep;
    }

    public function getWarningTimeAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $warning = [];
        $warningTime = \DB::table('workout_metrics')->where('workout_id', $workoutId)->select('min', 'max', 'interval')->where('metric', 'warning_time')->first();
        $count = 0;
        foreach (range($warningTime->min, $warningTime->max, $warningTime->interval) as $number) {
            $warning[$number] = $count;
            $count = $count + 1;
        }
        return $warning;
    }

}
