<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class EventActivities extends Model
{
    protected $fillable = ['event_id', 'event_activity_type_id', 'status'];  
    
    public function getStatusAttribute($value)
    {
        return (bool) $value;
    }
    
    public function event()
    {
        return $this->hasOne('App\Events', 'id', 'event_id');
    }

    public function participant()
    {
        return $this->hasMany('App\EventParticipants', 'event_activity_id');
    }

    public function sessions()
    {
        return $this->hasMany('App\EventSessions', 'event_id', 'event_id');
    }

    public function getUserJoinedAttribute($eventActivityId)
    {
        return (bool) \App\EventParticipants::where('event_activity_id', $eventActivityId)
            ->where('user_id', \Auth::user()->id)->exists();
    }

    public function getActivityStartedAttribute($eventActivityId)
    {
        $event = self::find($eventActivityId)->event;
        
        return (bool) (strtotime($event->start_date) < time());
    }

    public function getUserCountsAttribute($eventActivityId)
    {
        return \App\EventParticipants::where('event_activity_id', $eventActivityId)->count();
    }

    public function getUserScoreAttribute($eventActivityId)
    {
        $eventActivityTypeId = self::find($eventActivityId)->event->event_activity_type_id;

        if ($eventActivityTypeId) {
            $session = \App\EventSessions::where('event_activity_id', $eventActivityId)
                            ->where('participant_id', \Auth::id())->first();
            if ($session) {
                // Score based on Speed
                if ($eventActivityTypeId == 1) {
                    return (int) $session->avg_speed;
                }
                
                // Score based on Power
                elseif ($eventActivityTypeId == 2) {
                    return (int) $session->avg_force;
                }

                // Score based on Endurance
                elseif ($eventActivityTypeId == 3) {
                    return (int) $session->punches_count;
                }
            }
        }

        return 0;
    }
}