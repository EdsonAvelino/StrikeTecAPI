<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PushTypes extends Model
{

    const BATTLE_INVITE = 1;
    const BATTLE_RESEND = 2;
    const BATTLE_ACCEPT_DECLINE = 3;
    const BATTLE_CANCEL = 4;
    
    const CHAT_SEND_MESSAGE = 5;
    const CHAT_READ_MESSAGE = 6;

    public $timestamps = false;
}