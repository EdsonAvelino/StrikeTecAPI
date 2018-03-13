<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IapProducts extends Model
{
    public function getPlatformAttribute($platform)
    {
        return strtoupper($platform);
    }

    public function setPlatformAttribute($Platform)
    {
        $this->attributes['platform'] = strtoupper($platform);
    }
}