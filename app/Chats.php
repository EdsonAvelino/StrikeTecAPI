<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chats extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'chat_id', 'user_one', 'user_two'
    ];

}
