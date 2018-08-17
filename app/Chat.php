<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{

    /**
     *
     * @var string
     */
    protected $table = 'chats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
            'user_one', 
            'user_two'
    ];

    /**
     * Get the messages for the chat.
     */
    public function messages()
    {
        return $this->hasMany('App\ChatMessages');
    }

}
