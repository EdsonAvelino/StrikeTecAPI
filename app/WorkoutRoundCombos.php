<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkoutRoundCombos extends Model
{
    protected $fillable = [
        'workout_round_id',
        'combo_id'
    ];

    public $timestamps = false;

    public function combo()
    {
        return $this->hasOne('App\Combos', 'id');
    }

    public function getKeySetAttribute($comboId)
    {
        return \App\Combos::getKeySet($comboId);
    }
}