<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'facebook_id',
        'first_name',
        'last_name',
        'coach_user',
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
        'country_id',
        'state_id',
        'city_id'
    ];

    public function leaderboard()
    {
        return $this->hasOne('App\Leaderboard', 'id', 'user_id');
    }

    public function preferences()
    {
        return $this->hasOne('App\ClientPreferences', 'client_id');
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

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($fbId = $model->facebook_id) {
                $model->photo_url = "http://graph.facebook.com/$fbId/picture?width=600&height=600";
            }
        });

        static::created(function ($client) {
            ClientPreferences::create([
                'client_id' => $client->id,
                'public_profile' => true,
                'show_achivements' => true,
                'show_training_stats' => true,
                'show_challenges_history' => true,
            ]);
            
            Leaderboard::create([
                'user_id' => $client->id,
                'sessions_count' => 0,
                'punches_count' => 0
            ]);
        });

        static::deleting(function($client) {
            // TODO Cleanup when client deleted, delete all their data & settings 
        });
    }

    public function getAgeAttribute($birthday)
    {
        return ($birthday) ? \Carbon\Carbon::parse($birthday)->age : null;
    }

    public function getPointsAttribute($clientId)
    {
        $leaderboard = Leaderboard::where('client_id', $clientId)->first();

        return ( (!empty($leaderboard)) ? $leaderboard->punches_count : 0 );
    }

    // return minimum fields of client
    // first_name, last_name, photo_url, and points
    public static function get($clientId)
    {
        return self::select([
            'id',
            'first_name',
            'last_name',
            'coach_user',
            'photo_url',
            \DB::raw('id as points')
        ])->where('id', $clientId)->first();
    }
}
