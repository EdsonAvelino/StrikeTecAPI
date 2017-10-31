<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityTypes extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'activity_id',
        'type_name',
    ];

}
