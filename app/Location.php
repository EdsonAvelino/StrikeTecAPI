<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

Class Location extends Model
{
    protected $filable = [
        'name'
    ];
    
    public function Location()
    {
         return $this->hasMany('App\Event')->select('name as location_name');
    }
}