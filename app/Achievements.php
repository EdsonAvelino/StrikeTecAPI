<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Achievements extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'description',
        'image',
        'all_count',
        'male',
        'female'
    ];
    protected $hidden = [

        'created_at',
        'updated_at'
    ];

}
