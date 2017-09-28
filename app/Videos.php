<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Videos extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'file',
        'thumbnail',    
        'view_counts',
        'duration',
        'author_name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function getFileAttribute($value)
    {
        if (strpos($value, 'youtube') > 0 || strpos($value, 'youtu.be') > 0)
            return $value;
        else
            return env('APP_URL').'/storage/videos/'.$value;
    }

    public function getThumbnailAttribute($value)
    {
        return env('APP_URL').'/storage/videos/thumbnails/'.$value;
    }
}