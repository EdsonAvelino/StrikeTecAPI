<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoalSession extends Model
{

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'goal_id', 'session_id'
    ];

    public function goals()
    {
        return $this->belongsTo('App\Goals');
    }

    public function sessions()
    {
        return $this->belongsTo('App\Sessions');
    }

}
