<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Goals extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'activity_id',
        'activity_type_id',
        'target',
        'start_date',
        'end_date'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function goalSessions()
    {
        return $this->hasMany('App\GoalSession','goal_id');
    }

}
