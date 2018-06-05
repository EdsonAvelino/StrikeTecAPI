<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject as AuthenticatableUserContract;

class User extends Model implements AuthenticatableContract, AuthenticatableUserContract, AuthorizableContract
{
    use Authenticatable,
        Authorizable;

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
        'height_feet',
        'height_inches',
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
        'country_id',
        'state_id',
        'city_id',
        'company_id'
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

    public function country()
    {
        return $this->hasOne('App\Countries', 'id', 'country_id');
    }

    public function state()
    {
        return $this->hasOne('App\States', 'id', 'state_id');
    }

    public function city()
    {
        return $this->hasOne('App\Cities', 'id', 'city_id');
    }

    public function company()
    {
        return $this->hasOne('App\Companies', 'id', 'company_id');
    }

    public function sessions()
    {
        return $this->hasMany('App\Sessions', 'user_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($fbId = $model->facebook_id) {
                $model->photo_url = "http://graph.facebook.com/$fbId/picture?type=large";
            }
        });

        static::created(function ($user) {
            UserPreferences::create([
                'user_id' => $user->id,
                'public_profile' => true,
                'show_achivements' => true,
                'show_training_stats' => true,
                'show_challenges_history' => true,
                'badge_notification' => true,
                'show_tutorial' => true,
                'unit' => UserPreferences::UNIT_ENGLISH
            ]);

            Settings::create([
                'user_id' => $user->id,
                'new_challenges' => true,
                'battle_update' => true,
                'tournaments_update' => true,
                'games_update' => true,
                'new_message' => true,
                'friend_invites' => true,
                'sensor_connectivity' => true,
                'app_updates' => true,
                'striketec_promos' => true,
                'striketec_news' => true
            ]);

            Leaderboard::create([
                'user_id' => $user->id,
                'sessions_count' => 0,
                'punches_count' => 0
            ]);
        });

        static::deleting(function($user) {
            // TODO Cleanup when user deleted, delete all their data & settings 
        });
    }

    public function setFirstNameAttribute($firstName)
    {
        $this->attributes['first_name'] = ucfirst(strtolower($firstName));
    }

    public function setLastNameAttribute($lastName)
    {
        $this->attributes['last_name'] = ucfirst(strtolower($lastName));
    }

    public function setEmailAttribute($email)
    {
        $this->attributes['email'] = strtolower($email);
    }

    public function getPhotoUrlAttribute($photo)
    {
        if ( (filter_var($photo, FILTER_VALIDATE_URL) === FALSE) ) {
            return (!empty($photo)) ? env('STORAGE_URL') . config('striketec.storage.users') . $photo : null;
        }

        // As it can be Facebook graph url
        return $photo;
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

    public function getPointsAttribute($userId)
    {
        $leaderboard = Leaderboard::where('user_id', $userId)->first();

        return ( (!empty($leaderboard)) ? $leaderboard->punches_count : 0 );
    }

    public function getSubscriptionCheckAttribute($userId)
    {
        return !(self::isSubscriptionActive($userId));
    }

    public static function isSubscriptionActive($userId)
    {
        // User's subscription
        $userSubscription = \App\UserSubscriptions::where('user_id', $userId)->first();

        // If not subscribed yet
        if (!$userSubscription) return false;

        // Check expire_at of subscription is greater than or equal to current date
        $expireAt = new \Carbon\Carbon($userSubscription->expire_at);
        return (bool) ( $expireAt->gte(\Carbon\Carbon::now()) );
    }

    public function getNumberOfChallengesAttribute($userId)
    {
        return Battles::where(function ($query) use($userId) {
                            $query->where(['user_id' => $userId])->orWhere(['opponent_user_id' => $userId]);
                        })
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->count();
    }

    // return minimum fields of user
    // first_name, last_name, photo_url, user_following, user_follower, points & gender
    public static function get($users)
    {
        $statement = self::select([
                    'id',
                    'first_name',
                    'last_name',
                    'photo_url',
                    'gender',
                    \DB::raw('id as user_following'),
                    \DB::raw('id as user_follower'),
                    \DB::raw('id as points')
        ]);

        if (is_array($users)) {
            return $statement->whereIn('id', $users)->get();
        } elseif (is_numeric($users)) {
            return $statement->where('id', $users)->first();
        }

        return null;
    }

    public static function getSubscription($userId)
    {
        // User's subscription
        $userSubscription = \App\UserSubscriptions::where('user_id', $userId)->first();

        // In case of user has not subscribed to any
        if (!$userId) return null;

        if (!$userSubscription) {
            $userSubscription = new \App\UserSubscriptions;
            $userSubscription->platform = "ANDROID";
            $userSubscription->iap_product_id = null;
        }

        // Fetch all IAP Products 
        $products = \App\IapProducts::select('id', 'key')->where('platform', $userSubscription->platform)->get();
        
        $data = [];
        foreach ($products as $product) {
            $data[$product->key] = ($product->id == $userSubscription->iap_product_id) ? true : false;
        }

        return $data;
    }
}