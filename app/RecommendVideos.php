<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecommendVideos extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'video_id',
        'recommend_video_id'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function RecommendedTags()
    {
        return $this->hasOne('App\RecommendTags', 'id', 'recommend_tag_id');
    }

    public function Videos()
    {
        return $this->hasMany('App\Videos', 'id', 'video_id');
    }

}
