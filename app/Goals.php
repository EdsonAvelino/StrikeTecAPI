<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Goals extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'user_goals';
    protected $fillable = [
        'user_id',
        'activity_id',
        'activity_type_id',
        'target',
        'start_date',
        'end_date'
    ];

}
