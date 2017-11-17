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
    
     protected $fillable = [
        'user_id', 'device_id', 'order_id', 'purchase_token', 'battle_left', 'tutorial_left', 'tournament_left', 'purchase_time', 'is_auto_renewing', 
        'subscription_id', 'expiry_date', 'is_cancelled'
    ];

}
