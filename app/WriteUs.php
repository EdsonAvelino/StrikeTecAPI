<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WriteUs extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'write_us';
    protected $fillable = [
        'email',
        'message',
        'subject',
    ];

}
