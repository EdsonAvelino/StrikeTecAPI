<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

Class EventUser extends Model{
    protected $fillable = ['user_id', 'event_id'];
    
    public function events()
    {
        return $this->belongsTo('App\Event', 'id');
    }
    
    public function users()
    {
        return $this->belongsTo('App\Users');
    }
    
    public function getUsersInfo($eventID) {
        $table = 'event_users'; 
       // echo '<pre>'; print($eventID); die;
        return DB::table($table)
                        ->Join('users', $table.'.user_id',  '=', 'users.id')
                        ->leftJoin('countries', 'users.country_id',  '=', 'countries.id')
                        ->leftJoin('states', 'users.state_id',  '=', 'states.id')
                        ->leftJoin('cities', 'users.city_id',  '=', 'cities.id')
                        ->select( 'users.first_name','users.last_name','users.birthday','users.gender','users.height','users.weight','users.email', 'states.name as state_name','countries.name as country_name', 'cities.name as city_name')
                        ->where($table.'.event_id', $eventID)
                        ->where($table.'.status', 0)
                        ->get();
    }
}