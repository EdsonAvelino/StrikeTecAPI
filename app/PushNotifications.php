<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PushNotifications extends Model
{
    protected $fillable = [
        'user_id',
        'type_id',
        'os',
        'payload'
    ];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function getOsAttribute($os)
    {
    	return strtoupper($os);
    }

    public function setOsAttribute($os)
    {
    	$this->attributes['os'] = strtoupper($os);
    }
}