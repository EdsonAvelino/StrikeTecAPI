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

    // Checks and updates current goal is accomplished
    public static function checkCurrentGoalAccomplished()
    {
        $goal = self::where('user_id', \Auth::id())->where('awarded', '!=', 1)->where('followed', 1)->first();  

        if ($goal) {
            $progress = (int) $goal->done_count * 100 / $goal->target;

            if ($progress >= 100) {
                $goal->awarded = 1;
                $goal->followed = null;
                $goal->save();
                
                return true;
            }
        }

        return false;
    }

    // Get Current Followed Goal 
    public static function getCurrentGoalId($userId)
    {
        $userId = (int) $userId;

        if (!$userId) return 0;

        return self::select('id')->where('user_id', $userId)->where('followed', 1)->first()->pluck('id');
    }
}
