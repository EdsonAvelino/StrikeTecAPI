<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VideoView extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'video_views';
    
    
    protected $fillable = [
        'user_id',
        'video_id',
        'watched_count'
    ];
}
