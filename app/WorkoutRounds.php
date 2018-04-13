<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkoutRounds extends Model
{
    protected $fillable = [
        'workout_id',
        'name'
    ];

    public $timestamps = false;

    public function combos()
    {
        return $this->hasMany('App\WorkoutRoundCombos', 'workout_round_id', 'id');
    }
}