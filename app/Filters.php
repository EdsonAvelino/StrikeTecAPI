<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Filters extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'filter_name'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    //get tags pass 1 for videos, 2 for combos, 3 for workout
    public static function getFilters($typeId)
    {
        return self::where('type', $typeId)->get();
    }

}
