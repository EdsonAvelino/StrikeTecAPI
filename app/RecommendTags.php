<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecommendTags extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name'
    ];
    protected $hidden = [

        'created_at',
        'updated_at'
    ];

}
