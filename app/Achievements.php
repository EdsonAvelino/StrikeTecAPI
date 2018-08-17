<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Achievements extends Model
{
    const BELT = 1;
    const PUNCHES_COUNT = 2;
    const MOST_PPM = 3;
    const ACCOMPLISH_GOAL = 4;
    const MOST_POWERFUL_PUNCH = 5;
    const TOP_SPEED = 6;
    const USER_PARTICIPATION = 7;
    const CHAMPION = 8;
    const ACCURACY = 9;
    const STRONG_MAN = 10;
    const SPEED_DEAMON = 11;
    const IRON_FIRST = 12;

    protected $fillable = [
        'id',
        'name',
        'sequence'
    ];
    
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function achievementType()
    {
        return $this->hasMany('App\AchievementTypes', 'achievement_id');
    }

}
