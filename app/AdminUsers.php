<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject as AuthenticatableUserContract;

class AdminUsers extends Model implements AuthenticatableContract, AuthenticatableUserContract, AuthorizableContract
{

    use Authenticatable,
        Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'is_web_admin',
        'is_fan_app_admin',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'is_web_admin',
        'is_fan_app_admin',
        'created_at',
        'updated_at'
    ];

    /**
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Eloquent model method
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    
    public function company()
    {
        return $this->hasOne('App\Companies', 'id', 'company_id');
    }
}
