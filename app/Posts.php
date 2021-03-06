<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Posts extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'post_type_id',
        'data_id',
        'likes',
    ];

    protected $hidden = [
        'user_id',
        'updated_at',
    ];

    protected static $titleTemplates = [
        1 => '_USER1_ shared _TEMPLATE_ battle history with _USER2_',
        2 => '_USER1_ shared a training session',
        3 => '_USER1_ has accomplished goal',
        4 => '_USER1_ has now BADGE'
    ];

    public static function boot()
    {
        parent::boot();
        
        $titleTemplates = self::$titleTemplates;

        static::creating(function ($model) use ($titleTemplates) {
            $model->title = $titleTemplates[$model->post_type_id];
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function data()
    {
        switch ($this->post_type_id) {
            case 1: // Battle
                return $this->hasOne('App\Battles', 'id', 'data_id');
                break;

            case 2: // Training Session
                return $this->hasOne('App\Sessions', 'id', 'data_id');
                break;

            case 3: // Goal
                return $this->hasOne('App\Goals', 'id', 'data_id');
                break;

            case 4: // Badge
                // return $this->hasOne('App\Sessions', 'id', 'data_id');
                break;
        }
    }

    public function likes()
    {
        return $this->hasMany('App\PostLikes', 'post_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany('App\PostComments', 'post_id', 'id');
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return strtotime($createdAt);
    }
}