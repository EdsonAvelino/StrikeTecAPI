<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPreferences extends Model
{
    const UNIT_ENGLISH = 0;
    const UNIT_METRIC = 1;

    protected $fillable = [
        'user_id',
        'public_profile',
        'show_achivements',
        'show_training_stats',
        'show_challenges_history',
        'badge_notification',
        'show_tutorial',
        'unit'
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