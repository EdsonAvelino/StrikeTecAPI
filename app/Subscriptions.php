<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscriptions extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'plan_type',
        'SKU',
        'tutorials',
        'tournaments',
        'battles',
    ];

}
