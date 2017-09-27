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
        'view_counts',
        'duration',
        'author_name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}