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
        'iap_product_id',
        'platform',
        'is_auto_renewable', 
        'purchased_at',
        'expire_at'
    ];

    public function getPlatformAttribute($os)
    {
        return strtoupper($os);
    }

    public function setPlatformAttribute($os)
    {
        $this->attributes['platform'] = strtoupper($os);
    }

    public function setPurchasedAtAttribute($purchasedAt)
    {
        $purchasedAt = ($purchasedAt) ? date('Y-m-d h:i:s', $purchasedAt) : null;
        
        $this->attributes['purchased_at'] = $purchasedAt;
    }

    public function setExpireAtAttribute($expireAt)
    {
        $expireAt = ($expireAt) ? date('Y-m-d h:i:s', $expireAt) : null;
        
        $this->attributes['expire_at'] = $expireAt;
    }
}
