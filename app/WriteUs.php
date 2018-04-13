<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WriteUs extends Model
{
    protected $table = 'write_us';

    protected $fillable = [
        'email',
        'message',
        'subject',
    ];
}
