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
        'avg_speed',
        'avg_force',
        'punch_count',
        'max_force',
        'max_speed',
    ];

    public function rounds()
    {
        return $this->hasMany('App\TrainingSessionRounds', 'training_session_id');
    }

    public function roundsPunches()
    {
        return $this->hasMany('App\TrainingSessionRoundsPunches', 'session_round_id', 'id');
    }
}