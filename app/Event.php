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
        'id', 'location_id', 'company_id', 'event_title', 'description', 'to_date', 'to_time', 'from_date', 'from_time','all_day', 'type_of_activity',
    ];
    
    public function eventsUser()
    {
        return $this->hasMany('App\EventUser', 'event_id');
    }
   
    public function eventLocation()
    {
         return $this->belongsTo('App\Location', 'location');
    }
    
    public function eventList($eventID, $company_id)
    {
        $table = 'events'; 
        return DB::table($table)
                        ->leftJoin('locations', $table.'.location_id',  '=', 'locations.id')
                        ->leftJoin('companies', $table.'.company_id',  '=', 'companies.id')
                        ->select( $table . '.*','locations.name as location_name', 'companies.company_name')
                        ->where($table.'.company_id', $company_id)
        ->where(function ($query) use ($eventID) {
        if($eventID) {
            $query->where('events.id' , $eventID);
        }
        })->get();
    }
    
    public function usersList($company_id)
    {
        $table = 'events';
        return DB::table($table)
                        ->Join('event_users', $table.'.id',  '=', 'event_users.event_id')
                        ->select( DB::raw('group_concat(event_users.event_id) as events'), 'event_users.user_id')
                        ->groupBy('event_users.user_id')
                        ->where($table.'.company_id', $company_id)->get();               
    }
}
