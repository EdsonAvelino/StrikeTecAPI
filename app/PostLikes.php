<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostLikes extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'post_id',
        'user_id',
    ];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->liked_at = $model->freshTimestamp();
        });
    }
}