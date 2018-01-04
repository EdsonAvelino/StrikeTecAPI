<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Goals extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'activity_id',
        'activity_type_id',
        'target',
        'start_at',
        'end_at',
        'awarded'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function goalSessions()
    {
        return $this->hasMany('App\GoalSession', 'goal_id');
    }

    public function getSharedAttribute($shared)
    {
        $shared = filter_var($shared, FILTER_VALIDATE_BOOLEAN);
        return ($shared) ? 'true' : 'false';
    }

    public static function getAccomplishedGoal()
    {
        $goal = self::where('user_id', \Auth::user()->id)
                        ->where('awarded', '!=', 1)
                        ->where('followed', 1)->first();
        $progress = 0;
        if ($goal) {
            $goalData = (int) $goal->done_count * 100 / $goal->target;
            if ($goalData >= 100) {
                $progress = 1;
                $goal->awarded = 1;
                $goal->save();
            }
        }

        return $progress;
    }

    // Calculate followed goal data
    public static function getCurrentGoal($userId)
    {
        $goalId = 0;
        $goal = self::where('user_id', $userId)
                ->where('followed', 1)
                ->first();

        if ($goal)
            $goalId = $goal->id;

        return (int) $goalId;
    }

}
