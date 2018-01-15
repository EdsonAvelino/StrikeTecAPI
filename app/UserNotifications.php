<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserNotifications extends Model
{
    protected $fillable = [
        'user_id',
        'data_user_id',
        'notification_type_id',
        'data_id',
        'text'
    ];

    protected $hidden = [
        'read_at',
        'data_user_id',
        'data_id'
    ];
    
    public $timestamps = false;

    const FOLLOW = 1;
    const BATTLE_CHALLENGED = 2;
    const BATTLE_FINISHED = 3;
    const FEED_POST_LIKE = 4;
    const FEED_POST_COMMENT = 5;
    const TOURNAMENT_ACTIVITY_INVITE = 6;
    
    protected static $textTemplates = [
        1 => '_USER1_ is now following you',
        2 => '_USER1_ has challenged you for battle',
        3 => '_USER1_ has finished battle',
        4 => '_USER1_ likes your post',
        5 => '_USER1_ has commented on your post',
        6 => '_USER1_ has invited you to event activity'
    ];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public static function generate($type, $toUserId, $dataUserId, $dataId = null)
    {
        return self::create([
            'user_id' => $toUserId,
            'data_user_id' => $dataUserId,
            'notification_type_id' => $type,
            'data_id' => $dataId,
            'text' => self::$textTemplates[$type]
        ]);
    }

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function opponentUser()
    {
        return $this->hasOne('App\User', 'id', 'data_user_id');
    }

    public function getCreatedAtAttribute($createdAt)
    {
        return strtotime($createdAt);
    }
}