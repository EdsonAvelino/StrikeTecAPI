<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoalAchievements extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'goal_achievements';
    protected $fillable = [
        'id',
        'user_id',
        'goal_id',
        'achievement_id',
        'user_achievement_id',
        'achievement_type_id',
        'awarded',
        'metric_value',
        'count',
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

    // Calculate followed goal data
    public static function goalAchievements($userId, $achievementId, $achievementTypeId, $matricValue, $count, $userAchievementId)
    {
        $today = date("Y-m-d H:i:s");
        $goal = Goals::where('user_id', $userId)
                ->where('followed', 1)
                ->where('end_at', "<=", $today)
                ->first();

        if ($goal) {
            GoalAchievements::updateOrCreate([
                'user_id' => $userId,
                'goal_id' => $goal->id,
                'achievement_id' => $achievementId,
                'achievement_type_id' => $achievementTypeId], [
                'metric_value' => $matricValue,
                'user_achievement_id' => $userAchievementId,
                'awarded' => true,
                'count' => $count,
            ]);
        }
    }

    public static function getGoalAchievements($userId, $goalId)
    {
        $userAchievements = self::with(['achievementType', 'achievement'])->where('user_id', $userId)
                        ->where('goal_id', $goalId)
                        ->get()->toArray();
        $result = [];
        foreach ($userAchievements as $achievements) {
            $resultData['id'] = $achievements['id'];
            $resultData['achievement_id'] = $achievements['achievement']['id'];
            $resultData['achievement_name'] = $achievements['achievement']['name'];
            $resultData['badge_name'] = $achievements['achievement_type']['name'];
            $resultData['description'] = $achievements['achievement_type']['description'];
            $resultData['image'] = $achievements['achievement_type']['image'];
            $resultData['badge_value'] = $achievements['metric_value'];
            $resultData['count'] = $achievements['count'];
            $resultData['awarded'] = (boolean) $achievements['awarded'];
            $result[] = $resultData;
        }
        return $result;
    }

}
