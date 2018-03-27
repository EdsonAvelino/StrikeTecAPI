<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VideoTags extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tag_id',
        'video_id'
    ];
}