<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject as AuthenticatableUserContract;

class User extends Model implements AuthenticatableContract,
    AuthenticatableUserContract,
    AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'facebook_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'gender',
        'birthday',
        'weight',
        'height',
        'left_hand_sensor',
        'right_hand_sensor',
        'left_kick_sensor',
        'right_kick_sensor',
        'is_spectator',
        'stance',
        'is_spectator',
        'show_tip',
        'photo_url',
        'city_id',
        'state_id',
        'country_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    // protected $attributes = array(
    //     'age' => '',
    // );

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

    public function leaderboard()
    {
        return $this->hasOne('App\Leaderboard', 'id', 'user_id');
    }

    public function preferences()
    {
        return $this->hasOne('App\UserPreferences', 'user_id');
    }

    public function followers()
    {
        return $this->hasMany('App\UserConnections', 'follow_user_id');
    }

    public function following()
    {
        return $this->hasMany('App\UserConnections', 'user_id');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            UserPreferences::create([
                'user_id' => $user->id,
                'public_profile' => true,
                'show_achivements' => true,
                'show_training_stats' => true,
                'show_challenges_history' => true,
            ]);
        });
    }

    public function getAgeAttribute($birthday)
    {
         return ($birthday) ? \Carbon\Carbon::parse($birthday)->age : null;
    }

    public function getUserFollowingAttribute($userId)
    {
        $following = UserConnections::where('follow_user_id', $userId)
            ->where('user_id', \Auth::user()->id)->exists();

        return (bool) $following;
    }

    public function getUserFollowerAttribute($userId)
    {
        $follower = UserConnections::where('user_id', $userId)
            ->where('follow_user_id', \Auth::user()->id)->exists();

        return (bool) $follower;
    }
}