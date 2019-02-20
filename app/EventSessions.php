<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventSessions extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'participant_id',
        'event_activity_id',
        'start_time',
        'end_time',
        'plan_id',
        'avg_speed',
        'avg_force',
        'punches_count',
        'max_force',
        'max_speed',
        'best_time',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function participant()
    {
        return $this->hasOne('App\User', 'id', 'participant_id')
            ->select([
                'id',
                'first_name',
                'last_name',
                'photo_url',
                'gender',
                \DB::raw('id as user_following'),
                \DB::raw('id as user_follower'),
                \DB::raw('id as points')
            ]);
    }    
 
    public function eventActivity()
    {
        return $this->hasMany('\App\FanActivity', 'id', 'activity_id');
    }
    
}