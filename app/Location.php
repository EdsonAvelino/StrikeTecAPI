<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name'
    ];
    
    public function Location()
    {
         return $this->hasMany('App\Event')->select('name as location_name');
    }
}