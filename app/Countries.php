<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Countries extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'abbr',
        'name',
        'phone_code',
    ];

    public $timestamps = false;
}