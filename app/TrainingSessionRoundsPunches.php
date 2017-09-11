<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingSessionRoundsPunches extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_round_id',
        'punch_time',
        'punch_duration',
        'force',
        'speed',
        'punch_type',
        'hand',
    ];
}