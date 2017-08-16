<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordResets extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'code',
        'key',
        'expires_at',
    ];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Sets some unique key
            $model->key = bin2hex(openssl_random_pseudo_bytes(32));

            // Sets the expires_at
            $expires_at = \Carbon\Carbon::now();
            $model->expires_at = $expires_at->addHours(3);

            // Sets the created_at
            $model->created_at = $model->freshTimestamp();
        });
    }
}