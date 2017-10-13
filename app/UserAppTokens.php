<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserAppTokens extends Model
{
    protected $fillable = [
        'user_id',
        'os',
        'token'
    ];
}