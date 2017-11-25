<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaggedVideos extends Model
{

    public $timestamp = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tag_id',
        'video_id'
    ];

}