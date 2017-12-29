<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventSessionPunche extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_session_id',
        'punch_time',
        'punch_duration',
        'force',
        'speed',
        'punch_type',
        'hand',
    ];

}