<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserAchievements extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'user_achievements';
    protected $fillable = [
        'user_id',
        'punch_count',
        'punches_per_min',
        'goal_accomplish',
        'powerful_punch',
        'top_speed',
        'user_participation',
        'champion',
        'accuracy',
        'strong_man',
        'speed_demon',
        'iron_fist'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getAchievements($userId)
    {
        $achivements = Achievements::select('name', 'description', 'image')->get()->toArray();
        $userAchievements = UserAchievements::where('user_id', $userId)->first();
        if ($userAchievements === null) {
            $userAchievements = [
                'punch_count' => 0,
                'punches_per_min' => 0,
                'goal_accomplish' => 0,
                'powerful_punch' => 0,
                'top_speed' => 0,
                'user_participation' => 0,
                'champion' => 0,
                'accuracy' => 0,
                'strong_man' => 0,
                'speed_demon' => 0,
                'iron_fist' => 0,
                'belts' => 0
            ];
            $userAchievements = (object) $userAchievements;
        }
        $achivements[0]['count'] = $userAchievements->belts;
        $achivements[1]['count'] = $userAchievements->punch_count;
        $achivements[2]['count'] = $userAchievements->punches_per_min;
        $achivements[3]['count'] = $userAchievements->goal_accomplish;
        $achivements[4]['count'] = $userAchievements->powerful_punch;
        $achivements[5]['count'] = $userAchievements->top_speed;
        $achivements[6]['count'] = $userAchievements->user_participation;
        $achivements[7]['count'] = $userAchievements->champion;
        $achivements[8]['count'] = $userAchievements->accuracy;
        $achivements[9]['count'] = $userAchievements->strong_man;
        $achivements[10]['count'] = $userAchievements->speed_demon;
        $achivements[11]['count'] = $userAchievements->iron_fist;

        for ($count = 0; $count <= 11; $count++) {
            $achivements[$count]['share'] = false;
            if ($achivements[$count]['count'] > 0) {
                $achivements[$count]['awarded'] = true;
            }
        }
        return $achivements;
    }

}
