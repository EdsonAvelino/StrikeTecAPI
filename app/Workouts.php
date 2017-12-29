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
    
    public function metrics()
    {
        return $this->hasMany('App\WorkoutMetrics', 'workout_id', 'id');
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

        $filters = \DB::table('workout_metrics')->where('workout_id', $workoutId)->select('min','max','interval')->where('metric', 'round_time')->first();

        return $filters;
    }
    
    public function getRestTimeAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $filters = \DB::table('workout_metrics')->where('workout_id', $workoutId)->select('min','max','interval')->where('metric', 'rest_time')->first();

        return $filters;
    }
    
    
    public function getPrepareTimeAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $filters = \DB::table('workout_metrics')->where('workout_id', $workoutId)->select('min','max','interval')->where('metric', 'prepare_time')->first();

        return $filters;
    }
    
    public function getWarningTimeAttribute($workoutId)
    {
        $workoutId = (int) $workoutId;

        if (empty($workoutId)) {
            return null;
        }

        $filters = \DB::table('workout_metrics')->where('workout_id', $workoutId)->select('min','max','interval')->where('metric', 'warning_time')->first();

        return $filters;
    }

}
