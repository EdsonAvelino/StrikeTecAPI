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
        'session_id',
        'goal_id'
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
        $userAchievements = UserAchievements::with(['achievementType', 'achievement'])
                ->where('user_id', $userId)
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
            $resultData['image'] =  $achievements['achievement_type']['image'];
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
        $userAchievements = UserAchievements::select('achievement_id', 'achievement_type_id', \DB::raw('MAX(metric_value) as metric_value'), 'awarded', 'count', 'shared')->with('achievementType')
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
                    $resultData['description'] = $beltBadge['description'];
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
                    $resultData['description'] = $badge['description'];
                    $resultData['image'] =  $badge['image'];
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
            $resultData['description'] = $beltBadge['description'];
            $resultData['image'] =  $beltBadge['image'];
            $resultData['badge_value'] = 0;
            $resultData['awarded'] = false;
            $resultData['count'] = 0;
            $resultData['shared'] = false;
            $result[] = $resultData;
        }
        return $result;
    }

    public static function get($achievementId)
    {
        $userAchievement = \App\UserAchievements::select('achievement_id', 'achievement_type_id', 'metric_value as badge_value', 'awarded', 'count', 'shared')->where('id', $achievementId)->first();

        $achievement = $userAchievement->toArray();

        $_achievement = $userAchievement->achievement;
        $_achievementType = \App\AchievementTypes::where('id', $userAchievement->achievement_type_id)->first();

        unset($achievement['achievement_type_id']);
        $achievement['achievement_name'] = $_achievement->name;

        $achievement['awarded'] = (boolean) $userAchievement->awarded;
        $achievement['badge_name'] = $_achievementType->name;
        $achievement['shared'] = (boolean) $userAchievement->shared;
        $achievement['description'] = $_achievementType->description;
        $achievement['image'] =  $_achievementType->image;

        return $achievement;
    }

    // Running achievement schedular, source: App > Kernal > schedule()
    // TODO optimize this method for large number of users
    public static function runScheduler()
    {
        $users = User::select('id')->get();

        foreach ($users as $user) {
            // $text = "Achievement has been assigned to user #" . $user->id;
            
            // Todo log this process in better way!
            // Probably do weekly log in few rows, just like summary
            // \DB::insert('INSERT INTO scheduler_log (log, created_at) VALUES (?, ?)', [$text, $date]);
            
            self::process($user);
        }
    
        // Following achievements are resetting every week, so deleting assigned achievements
        // TODO soft delete here, so users' achievement history isn't lost

        UserAchievements::where('achievement_id', Achievements::ACCURACY)->delete();
        UserAchievements::where('achievement_id', Achievements::STRONG_MAN)->delete();        
        UserAchievements::where('achievement_id', Achievements::IRON_FIRST)->delete();        
    }

    private static function process($user)
    {
        $achievements = Achievements::select('id')->orderBy('sequence')->get();
        
        // Some achievements are assigned to next week, so we're processing them here
        foreach ($achievements as $achievement) {
            switch ($achievement->id) {
                case Achievements::USER_PARTICIPATION:
                    $lastWeek = strtotime("-1 week +1 day");
                    $lastWeekStart = strtotime("last monday midnight", $lastWeek);
                    $lastWeekEnd = strtotime("next monday midnight", $lastWeekStart)-1;

                    $lastWeekSessionsCount = \App\Sessions::where('user_id', $user->id)
                        ->where('start_time', '>', ($lastWeekStart * 1000))
                        ->where('start_time', '<', ($lastWeekEnd * 1000))
                        ->count();

                    if ($lastWeekSessionsCount > 0) {
                        $badge = AchievementTypes::select('id')
                                ->where('achievement_id', Achievements::USER_PARTICIPATION)
                                ->where('min', '<', $lastWeekSessionsCount)
                                ->where('max', '>', $lastWeekSessionsCount)
                                ->first();

                        if ($badge) {
                            $_userAchievement = UserAchievements::where('achievement_id', Achievements::USER_PARTICIPATION)
                                    ->where('user_id', $userId)
                                    ->where('achievement_type_id', $badge->id)
                                    ->first();

                            if ($_userAchievement) {
                                if ($_userAchievement->metric_value < $lastWeekSessionsCount) {
                                    $_userAchievement->achievement_type_id = $badge->id;
                                    $_userAchievement->metric_value = $lastWeekSessionsCount;
                                    $_userAchievement->shared = false;
                                    $_userAchievement->awarded = true;
                                    $_userAchievement->save();
                                }
                            } else {
                                $_userAchievement = UserAchievements::create([
                                    'user_id' => $userId,
                                    'achievement_id' => Achievements::USER_PARTICIPATION,
                                    'achievement_type_id' => $badge->id,
                                    'metric_value' => $lastWeekSessionsCount,
                                    'count' => 1,
                                    'awarded' => true,
                                ]);
                            }
                        }
                    }
                break;
            }
        }
    }

    public static function getGoalAchievements($userId, $goalId)
    {
        $userAchievements = UserAchievements::with(['achievementType', 'achievement'])
                ->where('user_id', $userId)
                ->where('goal_id', $goalId)
                ->get()
                ->toArray();
        $result = [];
        foreach ($userAchievements as $achievements) {
            $resultData['id'] = $achievements['id'];
            $resultData['achievement_id'] = $achievements['achievement']['id'];
            $resultData['achievement_name'] = $achievements['achievement']['name'];
            $resultData['badge_name'] = $achievements['achievement_type']['name'];
            $resultData['description'] = $achievements['achievement_type']['description'];
            $resultData['image'] =  $achievements['achievement_type']['image'];
            $resultData['badge_value'] = $achievements['metric_value'];
            $resultData['count'] = $achievements['count'];
            $resultData['shared'] = (boolean) $achievements['shared'];
            $resultData['awarded'] = (boolean) $achievements['awarded'];
            $result[] = $resultData;
        }
        return $result;
    }

}
