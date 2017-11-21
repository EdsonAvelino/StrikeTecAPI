<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaggedVideos extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tag_id',
        'video_id'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

}
