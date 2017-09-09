<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingSessions extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'training_type_id',
        'start_time',
        'end_time',
        'plan_id',
    ];
}