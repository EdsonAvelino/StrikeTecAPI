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

}
