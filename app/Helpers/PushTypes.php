<?php

namespace App\Helpers;

class PushTypes
{

    const BATTLE_INVITE = 1;
    const BATTLE_RESEND = 2;
    const BATTLE_ACCEPT = 3;
    const BATTLE_DECLINE = 4;
    const BATTLE_CANCEL = 5;
    
    const CHAT_SEND_MESSAGE = 6;
    const CHAT_READ_MESSAGE = 7;

    const BATTLE_FINISHED = 8;
    
    const TOURNAMENT_ACTIVITY_INVITE = 9;

    const CHAT_EDIT_MESSAGE = 10;

    public static function getSilentPushListForIOS()
    {
    	return [
    		self::CHAT_READ_MESSAGE
    	];
    }
}