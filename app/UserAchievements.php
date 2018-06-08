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
    public static function runScheduler()
    {
        $users = User::select('id', 'gender')->get();

        foreach ($users as $user) {
            $text = "Achievement has been assigned to user #" . $user->id;
            
            // Todo log this process in better way!
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
        $perviousMonday = strtotime('previous monday');
        
        $achievements = Achievements::select('id')->orderBy('sequence')->get();
        
        // Some achievements are assigned to next week, so we're processing them here
        // Some resets everyweek
        foreach ($achievements as $achievement) {
            switch ($achievement->id) {
                case Achievements::USER_PARTICIPATION:
                    $userParticpation = Sessions::getUserParticpation($userId, $perviousMonday);
                    
                    $lastWeekSessionsCount = Sessions::where('user_id', $user->id)
                        ->whereRaw('YEARWEEK(FROM_UNIXTIME(start_time / 1000), 1) = YEARWEEK("'. date('Y-m-d', $perviousMonday) .'", 1)')->count();

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
                                    }
                                } else {
                                    $userAchievements = UserAchievements::create([
                                        'user_id' => $userId,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $achievementType->id,
                                        'metric_value' => $userParticpation,
                                        'count' => 1,
                                        'awarded' => true,
                                    ]);
                                }
                            } else {
                                if ($userParticpationData)
                                    UserAchievements::where('achievement_id', $achievement->id)->delete();
                            }
                        }
                    }
                break;
            }
        }
        return;
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
