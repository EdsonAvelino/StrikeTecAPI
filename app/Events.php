<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Events extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'company_id',
        'location_id',
        'title',
        'description',
        'image',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'all_day',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function participants()
    {
        return $this->hasManyThrough('App\EventParticipants', 'App\EventActivities', 'event_id', 'event_activity_id')->limit(9);
    }

    public function eventUser()
    {
        return $this->hasMany('App\EventUser', 'event_id');
    }

    public function eventSessions()
    {
        return $this->hasMany('App\EventSession', 'event_id');
    }

    public function eventActivity()
    {
        return $this->hasMany('App\EventFanActivity', 'event_id');
    }

    public function eventLocation()
    {
        return $this->belongsTo('App\Location', 'location');
    }

    public function getStatusAttribute($value)
    {
        return (bool) $value;
    }

    public function getAllDayAttribute($value)
    {
        return (bool) $value;
    }

    public function getJoinedAttribute($eventId)
    {
        $eventUser = EventUser::select('status')->where('event_id', $eventId)
                        ->where('user_id', \Auth::user()->id)->first();

        if ($eventUser) {
            return (bool) $eventUser->status;
        }
        return FALSE;
    }

    public function getEventTypeAttribute($eventId)
    {
        $activityId = EventFanActivity::select('activity_id')->where('event_id', $eventId)
                        ->where('status', 0)->first();
        if ($activityId) {
            return (int) $activityId->activity_id;
        }
    }

    public function getUserDoneAttribute($eventId)
    {
        $eventTypeId = $this->getEventTypeAttribute($eventId);
        $session = FALSE;
        if ($eventTypeId) {
            $session = EventSession::where('event_id', $eventId)
                            ->where('activity_id', $eventTypeId)->where('participant_id', \Auth::user()->id)->exists();
        }
        return (bool) $session;
    }

    public function getFromDateAttribute()
    {
        return date('m/d/Y', strtotime($this->attributes['from_date']));
    }

    public function getToDateAttribute($value)
    {
        return date('m/d/Y', strtotime($value));
    }

    public function getUsersCountAttribute($eventId)
    {
        return EventUser::where('event_id', $eventId)->where('status', 1)->get()->count();
    }

    public function getLocationNameAttribute($locationId)
    {
        if ($locationId) {
            return Location::where('id', $locationId)->get()->first()->name;
        }
        return NULL;
    }

    public function getIsActiveAttribute($eventId)
    {
        $eventActivityStatus = EventActivities::where('event_id', $eventId)->where('status', 0)->first();

        if ($eventActivityStatus) {
            return TRUE;
        }

        return FALSE;
    }

    // public function getFinalizedAtAttribute($eventId)
    // {
    //     $eventIsActive = $this->getIsActiveAttribute($eventId);

    //     $concludedDate = NULL;

    //     if (!$eventIsActive) {
    //         $eventActivityStatus = EventActivities::where('event_id', $eventId)
    //                 ->where('status', 1)
    //                 ->orderBy('concluded_at', 'desc')
    //                 ->first();
    //         if ($eventActivityStatus) {
    //             $concludedDate = date('m/d/Y', strtotime($eventActivityStatus->concluded_at));
    //         }
    //     }

    //     return $concludedDate;
    // }

    /**
     * Function for get event and users list information
     * 
     * @param integer $eventID event id
     * @param integer $company_id id of company
     * @return type object of event list
     */
    public function eventsList($company_id)
    {
        return Self::select('*', \DB::raw('company_id as company_name'), \DB::raw('location_id as location_name'), \DB::raw('id as is_active'), \DB::raw('id as finalized_at'))
                        ->where('company_id', $company_id)->get();
    }

    /**
     * Function for get my event list information
     * 
     * @param integer $userID event id
     * @param integer $company_id id of company
     * @return type object of event list
     */
    public function myEventList($userID)
    {
        $table = 'events';
        return DB::table($table)
                        ->leftJoin('locations', $table . '.location_id', '=', 'locations.id')
                        ->leftJoin('companies', $table . '.company_id', '=', 'companies.id')
                        ->select($table . '.*', 'locations.name as location_name', 'companies.company_name')
                        ->where($table . '.user_id', $userID)->get();
    }

    public function eventActivityUsersList($eventID)
    {
        $table = 'events';
        return DB::table($table)
                        ->Join('event_users', $table . '.id', '=', 'event_users.event_id')
                        ->select('event_users.user_id as userID', $table . '.*')
                        ->where('event_users.event_id', $eventID)
                        ->where($table . '.id', $eventID)->get();
    }

    public function getCompanyNameAttribute($companyId)
    {
        return \App\Companies::where('id', $companyId)->first()->company_name;
    }

    public function getLocationAttribute($locationId)
    {
        return \App\Location::where('id', $locationId)->first()->name;
    }

    public function getImageAttribute($value)
    {
        if ($value) {
            return env('APP_URL') . '/storage/events/' . $value;
        }
    }

    public function getCountUsersWaitingApprovalAttribute($eventId)
    {
        return EventUser::where('event_id', $eventId)->where('status', 0)->where('is_cancelled', 0)->count();
    }

}