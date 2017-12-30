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
        return $this->hasOne('App\AchievementTypes', 'id', 'achievement_type_id');
    }

    public function achievement()
    {
        return $this->hasOne('App\Achievements', 'id', 'achievement_id');
    }

    public static function getSessionAchievements($userId, $sessionId)
    {
        $userAchievements = UserAchievements::with(['achievementType', 'achievement'])->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->get()
                ->toArray();
        $result = [];
        foreach ($userAchievements as $achievements) {
            $resultData['id'] = $achievements['id'];
            $resultData['achievement_id'] = $achievements['achievement']['id'];
            $resultData['achievement_name'] = $achievements['achievement']['name'];
            $resultData['badge_name'] = $achievements['achievement_type']['name'];
            $resultData['description'] = $achievements['achievement_type']['description'];
            $resultData['image'] = env('APP_URL') . '/storage/badges/' . $achievements['achievement_type']['image'];
            $resultData['badge_value'] = $achievements['metric_value'];
            $resultData['count'] = $achievements['count'];
            $resultData['shared'] = (boolean) $achievements['shared'];
            $resultData['awarded'] = (boolean) $achievements['awarded'];
            $result[] = $resultData;
        }
        return $result;
    }

    public static function getUsersAchievements($userId)
    {
        $achievements = Achievements::orderBy('sequence')->get()->keyBy('id');
        $userAchievements = UserAchievements::with('achievementType')
                ->where('user_id', $userId)
                ->groupBy('achievement_id')
                ->orderBy('achievement_id', 'desc')
                ->get()
                ->keyBy('achievement_id')
                ->toArray();
        $belts = Achievements::with('achievementType')->find(1)->toArray();
        $result = [];
        if ($userAchievements) {
            foreach ($achievements as $key => $checkData) {
                $resultData = [];
                if ($key == 1) {
                    $resultData['achievement_id'] = $belts['id'];
                    $resultData['achievement_name'] = $belts['name'];
                    $beltBadge = $belts['achievement_type'][0];
                    $resultData['badge_name'] = $beltBadge['name'];
                    $resultData['description'] = $beltBadge['name'];
                    $resultData['image'] = $beltBadge['image'];
                    $resultData['badge_value'] = 0;
                    $resultData['awarded'] = false;
                    $resultData['count'] = 0;
                    $resultData['shared'] = false;
                }
                if (isset($userAchievements[$key])) {
                    $userData = $userAchievements[$key];
                    $resultData['achievement_id'] = $userData['achievement_id'];
                    $resultData['achievement_name'] = $checkData['name'];
                    $badge = $userData['achievement_type'];
                    $resultData['badge_name'] = $badge['name'];
                    $resultData['description'] = $badge['name'];
                    $resultData['image'] = $badge['image'];
                    $resultData['badge_value'] = $userData['metric_value'];
                    $resultData['awarded'] = (boolean) $userData['awarded'];
                    $resultData['count'] = $userData['count'];
                    $resultData['shared'] = (boolean) $userData['shared'];
                }
                if ($resultData)
                    $result[] = $resultData;
            }
        } else {
            $resultData['achievement_id'] = $belts['id'];
            $resultData['achievement_name'] = $belts['name'];
            $beltBadge = $belts['achievement_type'][0];
            $resultData['badge_name'] = $beltBadge['name'];
            $resultData['description'] = $beltBadge['name'];
            $resultData['image'] = $beltBadge['image'];
            $resultData['badge_value'] = 0;
            $resultData['awarded'] = false;
            $resultData['count'] = 0;
            $resultData['shared'] = false;
            $result[] = $resultData;
        }
        return $result;
    }

    public static function schedulerForAchievements($userId)
    {
        $perviousMonday = strtotime('previous monday');
        $achievements = Achievements::orderBy('sequence')->get();
        foreach ($achievements as $achievement) {
            switch ($achievement->id) {

                case 7:
                    $userParticpation = Sessions::getUserParticpation($userId, $perviousMonday);
                    if ($userParticpation) {
                        $achievementType = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<', $userParticpation)
                                ->where('max', '>', $userParticpation)
                                ->first();
                        if ($achievementType) {
                            $userParticpationData = UserAchievements::where('achievement_id', $achievement->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', $achievement->id)
                                    ->first();
                            if ($userParticpation > 0) {
                                if ($userParticpationData) {
                                    if ($userParticpationData->metric_value < $userParticpation) {
                                        $userParticpationData->achievement_type_id = $achievementType->id;
                                        $userParticpationData->metric_value = $userParticpation;
                                        $userParticpationData->count = 1;
                                        $userParticpationData->shared = false;
                                        $userParticpationData->awarded = true;
                                        $userParticpationData->save();
                                        GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $userParticpation, 1, $userParticpationData->id);
                                    }
                                } else {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $userParticpation,
                                                'count' => 1,
                                                'awarded' => true,
                                    ]);
                                    GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $userParticpation, 1, $userAchievements->id);
                                }
                            } else {
                                if ($userParticpationData)
                                    UserAchievements::where('achievement_id', $achievement->id)->delete();
                            }
                        }
                    }
                    break;

                case 9:
                    $accuracy = Sessions::getAccuracy($perviousMonday);
                    if ($accuracy) {
                        $achievementType = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<', $accuracy)
                                ->where('max', '>', $accuracy)
                                ->first();
                        if ($achievementType) {
                            $accuracyData = UserAchievements::where('achievement_id', $achievement->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', $achievement->id)
                                    ->first();
                            if ($accuracy > 0) {
                                if ($accuracyData) {
                                    if ($accuracyData->metric_value < $accuracy) {
                                        $accuracyData->metric_value = $accuracy;
                                        $accuracyData->achievement_type_id = $achievementType->id;
                                        $accuracyData->count = 1;
                                        $accuracyData->shared = false;
                                        $accuracyData->awarded = true;
                                        $accuracyData->save();
                                        GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $accuracy, 1, $accuracyData->id);
                                    }
                                } else {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $accuracy,
                                                'count' => 1,
                                                'awarded' => true,
                                    ]);
                                    GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $accuracy, 1, $userAchievements->id);
                                }
                            } else {
                                if ($accuracyData)
                                    UserAchievements::where('achievement_id', $achievement->id)->delete();
                            }
                        }
                    }
                    break;

                case 10:
                    $strongMan = Sessions::getStrongMen(500, $userId, $perviousMonday);
                    if ($strongMan) {
                        $achievementType = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<', $strongMan)
                                ->where('max', '>', $strongMan)
                                ->first();
                        if ($achievementType) {
                            $strongManData = UserAchievements::where('achievement_id', $achievement->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', $achievement->id)
                                    ->first();
                            if ($strongMan > 0) {
                                if ($strongManData) {
                                    if ($strongManData->metric_value < $strongMan) {
                                        $strongManData->metric_value = $strongMan;
                                        $strongManData->count = 1;
                                        $strongManData->achievement_type_id = $achievementType->id;
                                        $strongManData->shared = false;
                                        $strongManData->awarded = true;
                                        $strongManData->save();
                                        GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $strongMan, 1, $strongManData->id);
                                    }
                                } else {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $strongMan,
                                                'count' => 1,
                                                'awarded' => true,
                                    ]);
                                    GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $strongMan, 1, $userAchievements->id);
                                }
                            } else {
                                if ($strongManData)
                                    UserAchievements::where('achievement_id', $achievement->id)->delete();
                            }
                        }
                    }
                    break;

                case 11:
                    $speedDemon = Sessions::getSpeedDemon(20, $userId, $perviousMonday);
                    if ($speedDemon) {
                        $achievementType = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<', $strongMan)
                                ->where('max', '>', $strongMan)
                                ->first();
                        if ($achievementType) {
                            $speedDemonData = UserAchievements::where('achievement_id', $achievement->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', $achievement->id)
                                    ->first();
                            if ($speedDemon > 0) {
                                if ($speedDemonData) {
                                    if ($speedDemonData->metric_value < $speedDemon) {
                                        $speedDemonData->metric_value = $speedDemon;
                                        $speedDemonData->achievement_type_id = $achievementType->id;
                                        $speedDemonData->count = 1;
                                        $speedDemonData->shared = false;
                                        $speedDemonData->awarded = true;
                                        $speedDemonData->save();
                                    }
                                    GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $speedDemon, 1, $speedDemonData->id);
                                } else {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $speedDemon,
                                                'count' => 1,
                                                'awarded' => true,
                                    ]);
                                    GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $speedDemon, 1, $userAchievements->id);
                                }
                            } else {
                                if ($speedDemonData)
                                    UserAchievements::where('achievement_id', $achievement->id)->delete();
                            }
                        }
                    }
                    break;

                case 12:
                    $ironFirst = Sessions::ironFirst($userId, $perviousMonday);
                    if ($ironFirst) {
                        $achievementType = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<', $ironFirst)
                                ->where('max', '>', $ironFirst)
                                ->first();

                        if ($achievementType) {
                            $ironFirstData = UserAchievements::where('achievement_id', $achievement->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', $achievement->id)
                                    ->first();
                            if ($ironFirst > 0) {
                                if ($ironFirstData) {
                                    if ($ironFirstData->metric_value < $ironFirst) {
                                        $ironFirstData->metric_value = $ironFirst;
                                        $ironFirstData->count = 1;
                                        $ironFirstData->achievement_type_id = $achievementType->id;
                                        $ironFirstData->shared = false;
                                        $ironFirstData->awarded = true;
                                        $ironFirstData->save();
                                        GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $ironFirst, 1, $ironFirstData->id);
                                    }
                                } else {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $ironFirst,
                                                'count' => 1,
                                                'awarded' => true,
                                    ]);
                                    GoalAchievements::goalAchievements($userId, $achievement->id, $achievementType->id, $ironFirst, 1, $userAchievements->id);
                                }
                            } else {
                                if ($ironFirstData)
                                    UserAchievements::where('achievement_id', $achievement->id)->delete();
                            }
                        }
                    }
                    break;
            }
        }
        return;
    }

    public static function get($achievementId)
    {
        $userAchievement = \App\UserAchievements::select('achievement_id', 'achievement_type_id', 'metric_value as badge_value', 'awarded', 'count', 'shared')->where('id', $achievementId)->first();

        $achievement = $userAchievement->toArray();

        $_achievement = $userAchievement->achievement;
        $_achievementType = \App\AchievementTypes::where('id', $userAchievement->achievement_type_id)->first();

        unset($achievement['achievement_type_id']);
        $achievement['achievement_name'] = $_achievement->name;

        $achievement['badge_name'] = $_achievementType->name;
        $achievement['description'] = $_achievementType->description;
        $achievement['image'] = env('APP_URL') . '/storage/badges/' . $_achievementType->image;

        return $achievement;
    }

    public static function achievementsSchedulerRun()
    {
        $date = date('y-m-d H:i:s');
        $users = User::select('id')->get();
        foreach ($users as $userId) {
            $text = "Achievement has been assigned to user  " . $userId->id;
            \DB::insert('insert into scheduler_log (log,created_at) values (?,?)', [$text, $date]);
            UserAchievements::schedulerForAchievements($userId->id);
        }
    }

}
