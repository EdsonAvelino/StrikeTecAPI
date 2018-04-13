<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ratings extends Model
{
	public $timestamps = false;

    protected $fillable  = [
        'user_id',
        'type_id',
        'plan_id',
        'rating',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->rated_at = $model->freshTimestamp();
        });
    }
}