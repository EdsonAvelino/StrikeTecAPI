<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SharedAchievements extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'achievement_data'
    ];
//    protected $hidden = [
//
//        'created_at'
//    ];

//    public function achievementType()
//    {
//        return $this->hasMany('App\AchievementTypes', 'achievement_id');
//    }

}
