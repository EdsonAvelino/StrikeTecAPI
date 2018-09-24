<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\StorageHelper;

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

    public function getImageAttribute($image)
    {

        return ($image) ? ( StorageHelper::getFile('badges/'.$image) ) : null;
    }
}
