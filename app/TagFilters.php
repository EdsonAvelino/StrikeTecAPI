<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TagFilters extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tag_id',
        'filter_name'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

}
