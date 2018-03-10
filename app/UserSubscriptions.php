<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSubscriptions extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'user_subscriptions';
    
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'app_subscription_plan_id',
        'device_id',
        'order_id',
        'purchase_token',
        'battles_left',
        'tutorials_left',
        'tournaments_left',
        'is_auto_renewing', 
        'is_cancelled',
        'purchased_at',
        'expiring_at'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->purchased_at = $model->freshTimestamp();
        });
    }
}
