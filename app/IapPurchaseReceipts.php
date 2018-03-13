<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IapPurchaseReceipts extends Model
{
    
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'platform',
        'iap_product_id',
        'order_id',
        'purchase_token',
        'receipt',
        'is_auto_renewing', 
        'is_cancelled'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->purchased_at = $model->freshTimestamp();
        });
    }
}
