<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AchievementTypes extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'achievement_id',
        'name',
        'description',
        'image',
        'config',
        'min',
        'max'
    ];
    protected $hidden = [

        'created_at',
        'updated_at'
    ];

    public function achievements()
    {
        return $this->hasOne('App\Achievements','id');
    }

}
