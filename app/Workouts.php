<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Workouts extends Model
{
    public static function get($workoutId)
    {
        $workout = self::find($workoutId);

        if (!$workout) return null;

        $_workout = $workout->toArray();

        // Loop thru rounds and get combos of round
        $datail = [];
        foreach ($workout->rounds as $round) {
            $_round = [];
            foreach ($round->combos as $combo) {
                $_round[] = \App\Combos::get($combo->combo_id);
            }
            $datail[] = $_round;
        };
        
        $_workout['detail'] = $datail;

        unset($_workout['trainer_id']);
        
        // Trainer
        $_workout['trainer'] = ['id' => $workout->trainer->id, 'type' => $workout->trainer->type, 'first_name' => $workout->trainer->first_name, 'last_name' => $workout->trainer->last_name];

        // Video
        $video = \App\Videos::select('*', \DB::raw('id as user_favorited'), \DB::raw('id as likes'))->where('type_id', \App\Types::WORKOUT)->where('plan_id', $workout->id)->first();

        $_workout['video'] = $video;
        
        // User rated workout
        $_workout['user_voted'] = (bool) \App\Ratings::where('user_id', \Auth::id())->where('type_id', \App\Types::WORKOUT)->where('plan_id', $workout->id)->exists();
        
        // Combo rating
        $rating = \App\Ratings::select(\DB::raw('SUM(rating) as sum_of_ratings'), \DB::raw('COUNT(rating) as total_ratings'))->where('type_id', \App\Types::WORKOUT)->where('plan_id', $workout->id)->first();
        $_workout['rating'] = number_format( (($rating->total_ratings > 0) ? $rating->sum_of_ratings / $rating->total_ratings : 0), 1 );

        // Skill levels
        $_workout['filters'] = \App\WorkoutTags::select('filter_id')->where('workout_id', $workout->id)->get()->pluck('filter_id');

        return $_workout;
    }

    public static function getOptimized($workoutId)
    {
        $workout = self::select('id', 'name', 'description')->where('id', $workoutId)->first();

        if (!$workout) return null;

        $_workout = $workout->toArray();

        // Loop thru rounds and get combos of round
        $datail = [];
        foreach ($workout->rounds as $round) {
            $_round = [];
            foreach ($round->combos as $combo) {
                $_round[] = \App\Combos::getOptimized($combo->combo_id);
            }
            $datail[] = $_round;
        };
        
        $_workout['detail'] = $datail;

        return $_workout;
    }

    public function rounds()
    {
        return $this->hasMany('App\WorkoutRounds', 'workout_id', 'id');
    }

    public function trainer()
    {
        return $this->belongsTo('App\Trainers');
    }

    public function tag()
    {
        return $this->hasOne('App\WorkoutTags', 'workout_id');
    }

    public function getRoundTimeAttribute($value)
    {
        return $this->getMetric('round_time', $value);
    }

    public function getRestTimeAttribute($value)
    {
        return $this->getMetric('rest_time', $value);
    }

    public function getPrepareTimeAttribute($value)
    {
        return $this->getMetric('prepare_time', $value);
    }

    public function getWarningTimeAttribute($value)
    {
        return $this->getMetric('warning_time', $value);
    }

    // Get matric value
    private function getMetric($key, $value)
    {
        $value = (int) $value;

        if (empty($value)) {
            return null;
        }

        $metric = \DB::table('workout_metrics')->select('min', 'max', 'interval')->where('metric', $key)->first();
        $range = range($metric->min, $metric->max, $metric->interval);

        return (int) array_search($value, $range);
    }

    public function getFilterAttribute($workoutId)
    {
        $filter = \DB::table('workout_tags')->select('filter_id')->where('workout_id', $workoutId)->first();

        return (!$filter) ? null : $filter->filter_id;
    }

    public function getRatingAttribute($workoutId)
    {
        $_rating = \App\Ratings::select(
            \DB::raw('SUM(rating) as sum_of_ratings'),
            \DB::raw('COUNT(rating) as total_ratings')
        )->where('type_id', \App\Types::WORKOUT)->where('plan_id', $workoutId)->first();

        $rating = ($_rating->total_ratings > 0) ? ($_rating->sum_of_ratings / $_rating->total_ratings) : 0;
        
        return number_format($rating, 1);
    }
}
