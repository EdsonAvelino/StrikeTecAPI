<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameLeaderboard extends Model
{

    protected $table = 'game_leaderboard';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'client_id',
        'game_id',
        'score',
        'distance'
    ];

    protected $hidden = ['created_at', 'updated_at'];
    
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function client()
    {
        return $this->belongsTo('App\Client');
    }
}
