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
        'event_id',
        'activity_id',
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

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'participant_id')
            ->select([
                'id',
                'first_name',
                'last_name',
                \DB::raw("CONCAT(`first_name`, ' ', `last_name`) as name"),
                'photo_url',
                'gender'
            ]);
    }    
}