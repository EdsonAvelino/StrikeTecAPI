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
        'id', 'user_id', 'location_id', 'company_id', 'event_title', 'description', 'to_date', 'to_time', 'from_date', 'from_time','all_day', 'type_of_activity',
    ];
    
    public function eventsUser()
    {
        return $this->hasMany('App\EventUser', 'event_id');
    }
   
    public function eventLocation()
    {
         return $this->belongsTo('App\Location', 'location');
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
                        ->leftJoin('locations', $table.'.location_id',  '=', 'locations.id')
                        ->leftJoin('companies', $table.'.company_id',  '=', 'companies.id')
                        ->select( $table . '.*','locations.name as location_name', 'companies.company_name')
                        ->where($table.'.company_id', $company_id)->get();
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
                        ->Join('event_users', $table.'.id',  '=', 'event_users.event_id')
                        ->leftJoin('companies', $table.'.company_id',  '=', 'companies.id')
                        ->select( DB::raw('group_concat(event_users.event_id) as events'), 'event_users.user_id')
                        ->groupBy('event_users.user_id')
                        ->where($table.'.company_id', $company_id)->get();                
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
                        ->leftJoin('locations', $table.'.location_id',  '=', 'locations.id')
                        ->leftJoin('companies', $table.'.company_id',  '=', 'companies.id')
                        ->select( $table . '.*','locations.name as location_name', 'companies.company_name')
                        ->where($table. '.user_id', $userID)->get();
    }
}
