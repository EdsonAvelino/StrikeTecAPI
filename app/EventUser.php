<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

Class EventUser extends Model
{

    protected $fillable = ['user_id', 'event_id'];

    /**
     * Function for get user information list
     * @param type $eventID
     * @return object object of users information.
     * 
     */
    public function getUsersInfo($eventID)
    {
        $table = 'event_users';
        return DB::table($table)
                        ->Join('users', $table . '.user_id', '=', 'users.id')
                        ->leftJoin('countries', 'users.country_id', '=', 'countries.id')
                        ->leftJoin('states', 'users.state_id', '=', 'states.id')
                        ->leftJoin('cities', 'users.city_id', '=', 'cities.id')
                        ->select('users.first_name', 'users.last_name', 'users.birthday', 'users.gender', 'users.height', 'users.weight', 'users.email', 'states.name as state_name', 'countries.name as country_name', 'cities.name as city_name')
                        ->where($table . '.event_id', $eventID)
                        ->where($table . '.status', 0)
                        ->get();
    }

    /**
     * Function for get user information list
     * @param type $userID
     * @return object object of users information.
     * 
     */
    public function getUsersList($userID)
    {
        $table = 'event_users';
        return DB::table($table)
                        ->Join('users', $table . '.user_id', '=', 'users.id')
                        ->leftJoin('countries', 'users.country_id', '=', 'countries.id')
                        ->leftJoin('states', 'users.state_id', '=', 'states.id')
                        ->leftJoin('cities', 'users.city_id', '=', 'cities.id')
                        ->select('users.id', 'users.first_name', 'users.last_name', 'users.photo_url', 'users.birthday', 'users.gender', 'users.height', 'users.weight', 'users.email', 'states.name as state_name', 'countries.name as country_name', 'cities.name as city_name')
                        ->distinct($table . '.user_id')
                        ->where($table . '.user_id', $userID)
                        ->where($table . '.status', 1)
                        ->first();
    }

}
