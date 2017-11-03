<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Posts extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'post_type_id',
        'data_id',
        'likes',
    ];
}