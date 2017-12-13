<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name'
    ];
    
    //get tags pass 1 for videos, 2 for combos, 3 for workout
    public static function getTags($typeId)
    {
        return self::where('type', $typeId)->get();
    }


}
