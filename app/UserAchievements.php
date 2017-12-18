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
        'id',
        'user_id',
        'achievement_id',
        'achievement_type_id',
        'awarded',
        'shared',
        'metric_value',
        'count',
        'session_id'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function achievementType()
    {
        return $this->hasMany('App\AchievementTypes', 'id', 'achievement_id');
    }

    public static function getSessionAchievements($userId, $sessionId)
    {
        $achievements = Achievements::with('achievementType')->orderBy('sequence')->get()->keyBy('id');
        $userAchievements = UserAchievements::select('metric_value', 'achievement_id')
                        ->where('user_id', $userId)
                        ->where('session_id', $sessionId)
                        ->get()->keyBy('achievement_id')->toArray();
        $result = [];
        foreach ($userAchievements as $achievementId => $checkData) {
            $metric = $checkData['metric_value'];
            $achievementType = $achievements[$achievementId]['achievementType'];
            $resultData = [];
            foreach ($achievementType as $typeId => $data) {
                if ($data['min'] > 0 && $data['max'] > 0) {
                    if ($metric >= $data['min'] && $metric <= $data['max']) {
                        if ($data['interval_value'] > 0) {
                            $metric = (int) ($metric / $data['interval_value']);
                            $metric = $metric * $data['interval_value'];
                        }
                        $resultData['achievement_id'] = $achievements[$achievementId]['id'];
                        $resultData['achievement_name'] = $achievements[$achievementId]['name'];
                        $resultData['name'] = $data['name'];
                        $resultData['description'] = $data['description'];
                        $resultData['image'] = $data['image'];
                        $resultData['badge_value'] = $metric;
                        $resultData['awarded'] = true;
                        $resultData['count'] = 1;
                    }
                } else {
                    if ($metric >= $data['config']) {
                        $resultData['achievement_id'] = $achievements[$achievementId]['id'];
                        $resultData['achievement_name'] = $achievements[$achievementId]['name'];
                        $resultData['name'] = $data['name'];
                        $resultData['description'] = $data['description'];
                        $resultData['image'] = $data['image'];
                        $resultData['badge_value'] = $metric;
                        $resultData['awarded'] = true;
                        $resultData['count'] = 1;
                        if ($data['achievement_id'] == 1 || $data['achievement_id'] == 4) {
                            $resultData['count'] = $metric;
                        }
                    }
                }
            }
            if ($resultData) {
                $result[] = $resultData;
            }
        }

        return $result;
    }

    public static function getUsersAchievements($userId)
    {

        $achievements = Achievements::with('achievementType')->orderBy('sequence')->get()->keyBy('id');
        $userAchievements = UserAchievements::select('metric_value', 'achievement_id')->where('user_id', $userId)->get()->keyBy('achievement_id')->toArray();
        $result = [];
        if ($userAchievements) {
            foreach ($achievements as $key => $checkData) {
                $metric = $userAchievements[$key]['metric_value'];
                $achievementType = $checkData['achievementType'];
                $resultData = [];
                if ($key == 1) {
                    $resultData['achievement_id'] = $checkData['id'];
                    $resultData['achievement_name'] = $checkData['name'];
                    $resultData['name'] = $achievementType[0]['name'];
                    $resultData['description'] = $achievementType[0]['name'];
                    $resultData['image'] = $achievementType[0]['image'];
                    $resultData['badge_value'] = $metric;
                    $resultData['awarded'] = false;
                    $resultData['count'] = 0;
                    $resultData['shared'] = false;
                }
                foreach ($achievementType as $typeId => $data) {
                    if ($data['min'] > 0 && $data['max'] > 0) {
                        if ($metric >= $data['min'] && $metric <= $data['max']) {
                            if ($data['interval_value'] > 0) {
                                $metric = (int) ($metric / $data['interval_value']);
                                $metric = $metric * $data['interval_value'];
                            }
                            $resultData['achievement_id'] = $checkData['id'];
                            $resultData['achievement_name'] = $checkData['name'];
                            $resultData['name'] = $data['name'];
                            $resultData['description'] = $data['description'];
                            $resultData['image'] = $data['image'];
                            $resultData['badge_value'] = $metric;
                            $resultData['awarded'] = true;
                            $resultData['count'] = 1;
                        }
                    } else {
                        if ($metric >= $data['config']) {
                            $resultData['achievement_id'] = $checkData['id'];
                            $resultData['achievement_name'] = $checkData['name'];
                            $resultData['name'] = $data['name'];
                            $resultData['description'] = $data['description'];
                            $resultData['image'] = $data['image'];
                            $resultData['badge_value'] = $metric;
                            $resultData['awarded'] = true;
                            $resultData['count'] = 1;
                            if ($data['achievement_id'] == 1 || $data['achievement_id'] == 4) {
                                $resultData['count'] = $metric;
                            }
                        }
                    }
                }
                if ($resultData)
                    $result[] = $resultData;
            }
        } else {
            $checkData = $achievements[1];
            $achievementType = $checkData['achievementType'];
            $resultData['achievement_id'] = $checkData['id'];
            $resultData['achievement_name'] = $checkData['name'];
            $resultData['name'] = $achievementType[0]['name'];
            $resultData['description'] = $achievementType[0]['name'];
            $resultData['image'] = $achievementType[0]['image'];
            $resultData['badge_value'] = 0;
            $resultData['awarded'] = false;
            $resultData['count'] = 0;
            $resultData['shared'] = false;
            $result[] = $resultData;
        }

        return $result;
    }

}
