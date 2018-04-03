<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewRatings extends Model
{
    protected $table = '__ratings';

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