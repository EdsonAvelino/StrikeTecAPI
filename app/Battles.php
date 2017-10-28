<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Battles extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'set_id',
        'type_id',
        'description'
    ];
}