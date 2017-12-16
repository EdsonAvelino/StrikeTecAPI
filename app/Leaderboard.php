<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{

    protected $table = 'leaderboard';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'sessions_count',
        'avg_speed',
        'avg_force',
        'punches_count',
        'max_speed',
        'max_force',
        'total_time_trained',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
