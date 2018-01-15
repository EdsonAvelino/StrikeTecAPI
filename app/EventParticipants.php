<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventParticipants extends Model
{
    protected $fillable = ['user_id', 'event_id', 'joined_via', 'status'];

    public $timestamps = false;

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

    public function events()
    {
        return $this->belongsTo('App\Events', 'id');
    }

    public function users()
    {
        return $this->belongsTo('App\User', 'user_id', 'id')->select('id', 'first_name', 'last_name', \DB::raw("CONCAT(COALESCE(`first_name`, ''), ' ',COALESCE(`last_name`, '')) as name"), 'photo_url', 'email');
    }
    
    public function getUserEventsAttribute($userId) {
        $events = self::select('event_id')->where('user_id', $userId)->where('event_id', '>', 0)->get()->toArray();
        return array_column($events, 'event_id');
    }
  
}