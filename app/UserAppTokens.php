<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserAppTokens extends Model
{
    protected $fillable = [
        'user_id',
        'os',
        'token'
    ];

    public function getOsAttribute($os)
    {
    	return strtoupper($os);
    }

    public function setOsAttribute($os)
    {
    	$this->attributes['os'] = strtoupper($os);
    }
}