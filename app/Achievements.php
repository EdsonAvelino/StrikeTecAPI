<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Achievements extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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
