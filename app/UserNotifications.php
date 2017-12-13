<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserNotifications extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'text'
    ];

    protected $hidden = [];
    
    const FOLLOW = 1;
    const BATTLE_CHALLENGED = 2;
    const BATTLE_FINISHED = 3;
    const FEED_POST_LIKE = 4;
    const FEED_POST_COMMENT = 5;
    
    protected static $textTemplates = [
        1 => '_USER1_ is now following you',
        2 => '_USER1_ has challenged you for battle',
        3 => '_USER1_ has finished battle',
        4 => '_USER1_ likes your post',
        5 => '_USER1_ has commented on your post'
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

    public function dataUser()
    {
        return $this->hasOne('App\User', 'id', 'data_user_id');
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return strtotime($createdAt);
    }
}