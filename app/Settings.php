<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'notification_settings';

    protected $fillable = [
        'user_id',
    ];

}
