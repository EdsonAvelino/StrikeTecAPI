<?php

namespace App\Models\Admin\Video;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = ['category_id', 'title', 'price', 'file', 'duration', 'author_name', 'thumbnail', 'created_at', 'updated_at'];
}
