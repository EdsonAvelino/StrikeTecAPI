<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewWorkoutRounds extends Model
{
    protected $table = '__workout_rounds';

    protected $fillable = [
        'workout_id',
        'name'
    ];

    public $timestamps = false;

    public function combos()
    {
        return $this->hasMany('App\NewWorkoutRoundCombos', 'workout_round_id', 'id');
    }
}