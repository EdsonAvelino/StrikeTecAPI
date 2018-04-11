<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewCombos extends Model
{
    protected $table = '__combos';

    protected $fillable = [
        'trainer_id',
        'name',
        'description'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function get($comboId)
    {
        $combo = self::select('*', \DB::raw('id as key_set'), \DB::raw('id as rating'))->where('id', $comboId)->first();

        if (!$combo) return null;

        $_combo = $combo->toArray();

        $_combo['detail'] = explode('-', $_combo['key_set']);
        
        unset($_combo['key_set']);
        unset($_combo['trainer_id']);

        // Trainer
        $_combo['trainer'] = ['id' => $combo->trainer->id, 'full_name' => $combo->trainer->first_name .' '. $combo->trainer->last_name];

        // Video
        $video = \App\NewVideos::select('*', \DB::raw('id as user_favorited'), \DB::raw('id as likes'))->where('type_id', \App\Types::COMBO)->where('plan_id', $comboId)->first();

        $_combo['video'] = $video;
        
        // User rated combo
        $_combo['user_voted'] = (bool) \App\NewRatings::where('user_id', \Auth::id())->where('type_id', \App\Types::COMBO)->where('plan_id', $comboId)->exists();
        
        // Combo rating
        $_combo['rating'] = $combo->rating;

        // Skill levels
        $_combo['filters'] = \App\NewComboTags::select('filter_id')->where('combo_id', $comboId)->get()->pluck('filter_id');

        return $_combo;
    }

    public static function getOptimized($comboId)
    {
        $combo = self::select('id', 'name', 'description', \DB::raw('id as key_set'))->where('id', $comboId)->first();

        if (!$combo) return null;

        $_combo = $combo->toArray();

        $_combo['detail'] = explode('-', $_combo['key_set']);
        
        unset($_combo['key_set']);
        unset($_combo['trainer_id']);

        return $_combo;
    }

    public function trainer()
    {
        return $this->belongsTo('App\NewTrainers');
    }

    public function getKeySetAttribute($comboId)
    {
        $comboId = (int) $comboId;

        if (empty($comboId)) {
            return null;
        }

        $keySet = \DB::table('combo_keys')->where('combo_id', $comboId)->pluck('punch_type_id')->toArray();

        return implode('-', $keySet);
    }

    public static function getKeySet($comboId)
    {
        $_this = new self();

        return $_this->getKeySetAttribute($comboId);
    }

    public function getRatingAttribute($comboId)
    {
        $_rating = \App\NewRatings::select(
            \DB::raw('SUM(rating) as sum_of_ratings'),
            \DB::raw('COUNT(rating) as total_ratings')
        )->where('type_id', \App\Types::COMBO)->where('plan_id', $comboId)->first();

        $rating = ($_rating->total_ratings > 0) ? ($_rating->sum_of_ratings / $_rating->total_ratings) : 0;
        
        return number_format($rating, 1);
    }
}
