<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPreferences extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'public_profile',
        'show_achivements',
        'show_training_stats',
        'show_challenges_history',
        'badge_notification',
        'show_tutorial'
    ];

    protected $hidden = [
        'id',
        'user_id',
        'updated_at'
    ];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
    }
}