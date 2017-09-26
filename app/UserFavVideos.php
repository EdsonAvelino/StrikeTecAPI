<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFavVideos extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'video_id',
    ];

    protected $hidden = [
        'created_at'
    ];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }
}