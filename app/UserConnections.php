<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserConnections extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'follow_user_id',
    ];

    protected $hidden = [
        'updated_at'
    ];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function followUser()
    {
        return $this->belongsTo('App\User', 'follow_user_id', 'id');
    }    
}