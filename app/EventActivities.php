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
        return $this->belongsTo('App\Events', 'event_id');
    }

    public function participants()
    {
        return $this->hasMany('App\EventParticipants', 'event_activity_id');
    }

    public function sessions()
    {
        return $this->hasMany('App\EventSessions', 'event_activity_id');
    }

    public function getUserJoinedAttribute($eventActivityId)
    {
        return (bool) \App\EventParticipants::where('event_activity_id', $eventActivityId)
            ->where('user_id', \Auth::id())->exists();
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
        return self::getUserScore(\Auth::id(), $eventActivityId);
    }

    public function getUserDoneAttribute($eventActivityId)
    {
        return (bool) \App\EventSessions::where('event_activity_id', $eventActivityId)
            ->where('participant_id', \Auth::id())->exists();
    }

    public static function getLeaderboardData($eventActivityId, $offset = 0, $limit = 20)
    {
        $eventActivityId = (int) $eventActivityId;
        
        $eventActivity = self::find($eventActivityId);

        // Null return in case of activity not found
        if (!$eventActivityId || !$eventActivity) return null;

        $eventActivityTypeId = (int) self::select('event_activity_type_id')->find($eventActivityId)->event_activity_type_id;

        $eventActivityParticipants = self::select([
                'id',
                'event_id',
                'event_activity_type_id'
            ])->with(['participants' => function($query) use ($offset, $limit) {
                $query->select(
                    'user_id',
                    'event_activity_id',
                    \DB::raw('id as user_score')
                )->where(function($query) {
                    $query->where('is_finished', 1)->orWhere('user_id', \Auth::id());
                })->orderBy('user_score', 'desc')->offset($offset)->limit($limit);
            }])->first();
        
        $leaderboardData = $eventActivityParticipants->toArray();
        $participants = [];

        foreach ( $leaderboardData['participants'] as $idx => $participant ) {
            $userId = $participant['user_id'];
            $user = \App\User::get($userId)->toArray();
            $user['user_score'] = self::getUserScore($userId, $eventActivityId);

            $participants[] = $user;
        }

        $leaderboardData['participants'] = $participants;

        return $leaderboardData;
    }

    public static function getUserScore($userId, $eventActivityId)
    {
        $eventActivityTypeId = self::find($eventActivityId)->event_activity_type_id;

        if (!$eventActivityTypeId) return 0;

        $session = \App\EventSessions::where('event_activity_id', $eventActivityId)
            ->where('participant_id', $userId)->first();

        if ($session) {
            // Score based on Speed
            if ($eventActivityTypeId == 1) {
                return (int) $session->max_speed;
            }
            
            // Score based on Power
            elseif ($eventActivityTypeId == 2) {
                return (int) $session->max_force;
            }

            // Score based on Endurance
            elseif ($eventActivityTypeId == 3) {
                return (int) $session->punches_count;
            }
        }
    }
}