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
}
