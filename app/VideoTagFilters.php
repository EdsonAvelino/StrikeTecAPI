<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VideoTagFilters extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'video_id',
        'tag_id',
    ];
}
