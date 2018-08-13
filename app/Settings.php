<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'notification_settings';

    protected $fillable = [
        'user_id',
        'new_challenges',
        'battle_update',
        'tournaments_update',
        'games_update',
        'new_message',
        'friend_invites',
        'sensor_connectivity',
        'app_updates',
        'striketec_promos',
        'striketec_news',
    ];

}
