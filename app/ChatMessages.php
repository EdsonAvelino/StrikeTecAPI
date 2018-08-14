<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChatMessages extends Model
{

    /**
     * @var string
     */
    protected $table = 'chat_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'chat_id',
        'read_flag',
        'user_id',
        'message',
    ];
    
    
    public function chat() 
    {
        return $this->belongsTo('App\Chat');
    }

}
