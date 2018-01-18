<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventParticipants extends Model
{
    protected $fillable = ['user_id', 'event_activity_id', 'joined_via', 'status'];

    public $timestamps = false;

    protected $hidden = ['joined_via', 'joined_at'];

    public function setJoinedViaAttribute($via)
    {
        $this->attributes['joined_via'] = strtoupper($via);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->joined_at = $model->freshTimestamp();
        });
    }
    
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id')
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
    
    public function getUserEventsAttribute($userId)
    {
        $events = self::select('event_id')->where('user_id', $userId)->where('event_id', '>', 0)->get()->toArray();
        
        return array_column($events, 'event_id');
    } 
}