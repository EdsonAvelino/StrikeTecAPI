<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Event extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'user_id', 'location_id', 'company_id', 'event_title', 'description', 'image', 'to_date', 'to_time', 'from_date', 'from_time', 'all_day', 'status'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function eventUser()
    {
        return $this->hasMany('App\EventUser', 'event_id');
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
    
    public function getUserRegisteredAttribute($eventId)
    {
        $registered = EventUser::where('event_id', $eventId)
                        ->where('user_id', \Auth::user()->id)->exists();
         return (bool) $registered;
    }
    
    public function getJoinedAttribute($eventId)
    {
        $eventUser = EventUser::select('status')->where('event_id', $eventId)
                        ->where('user_id', \Auth::user()->id)->first();

        if($eventUser){
            return (bool) $eventUser->status;
        }
        return FALSE;
    }

    /**
     * Function for get event and users list information
     * 
     * @param integer $eventID event id
     * @param integer $company_id id of company
     * @return type object of event list
     */
    public function eventsList($company_id)
    {
        $table = 'events';
        return DB::table($table)
                        ->leftJoin('locations', $table . '.location_id', '=', 'locations.id')
                        ->leftJoin('companies', $table . '.company_id', '=', 'companies.id')
                        ->select($table . '.*', 'locations.name as location_name', 'companies.company_name')
                        ->where($table . '.company_id', $company_id)->get();
    }

    /**
     * Function for get users list information
     * 
     * @param integer $company_id company id
     * @return type object of users list
     */
    public function usersList($company_id)
    {
        $table = 'events';
        return DB::table($table)
                        ->Join('event_users', $table . '.id', '=', 'event_users.event_id')
                        ->leftJoin('companies', $table . '.company_id', '=', 'companies.id')
                        ->select(DB::raw('group_concat(event_users.event_id) as events'), 'event_users.user_id')
                        ->groupBy('event_users.user_id')
                        ->where($table . '.company_id', $company_id)->get();
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

    public function getUsersCountAttribute($eventId)
    {
        return EventUser::where('event_id', $eventId)->where('status', 1)->get()->count();
    }

    public function getCompanyNameAttribute($companyId)
    {
        return Companies::where('id', $companyId)->get()->first()->company_name;
    }

    public function getLocationAttribute($locationId)
    {
        return Location::where('id', $locationId)->get()->first()->name;
    }

    public function getImageAttribute($value)
    {
        if ($value) {
            return env('APP_URL') . '/storage/fanuser/event/' . $value;
        }
    }

}
