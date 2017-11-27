<?php

namespace App\Models\Admin\Video;

use Illuminate\Database\Eloquent\Model;

class TaggedVideo extends Model
{
    protected $fillable = ['video_id', 'tag_id'];
}
