<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingSessionRounds extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'training_session_id',
        'start_time',
        'end_time',
        'avg_speed',
        'avg_force',
        'punches_count',
        'max_speed',
        'max_force',
        'best_time',
        'avg_time',
    ];
}