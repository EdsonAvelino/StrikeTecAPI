<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComboSets extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function get($comboSetId)
    {
        $comboSet = self::find($comboSetId);

        if (!$comboSet) return null;

        $_comboSet = $comboSet->toArray();
            
        // Combos
        foreach( ($comboSet->combos()->pluck('combo_id')) as $comboId ){
            $_comboSet['detail'][] = \App\Combos::get($comboId);
        }

        unset($_comboSet['trainer_id']);
        
        // Trainer
        $_comboSet['trainer'] = ['id' => $comboSet->trainer->id, 'full_name' => $comboSet->trainer->first_name .' '. $comboSet->trainer->last_name];

        // Video
        $video = \App\Videos::select('*', \DB::raw('id as user_favorited'), \DB::raw('id as likes'))->where('type_id', \App\Types::COMBO_SET)->where('plan_id', $comboSet->id)->first();

        $_comboSet['video'] = $video;
        
        // User rated combo
        $_comboSet['user_voted'] = (bool) \App\Ratings::where('user_id', \Auth::id())->where('type_id', \App\Types::COMBO_SET)->where('plan_id', $comboSet->id)->exists();
        
        // Combo rating
        $rating = \App\Ratings::select(\DB::raw('SUM(rating) as sum_of_ratings'), \DB::raw('COUNT(rating) as total_ratings'))->where('type_id', \App\Types::COMBO_SET)->where('plan_id', $comboSet->id)->first();
        $_comboSet['rating'] = number_format( (($rating->total_ratings > 0) ? $rating->sum_of_ratings / $rating->total_ratings : 0), 1 );

        // Skill levels
        $_comboSet['filters'] = \App\ComboSetTags::select('filter_id')->where('combo_set_id', $comboSet->id)->get()->pluck('filter_id');

        return $_comboSet;
    }

    public function combos()
    {
        return $this->hasManyThrough('App\ComboSetCombos', 'App\Combos', 'id', 'combo_set_id');
    }

    public function trainer()
    {
        return $this->belongsTo('App\Trainers');
    }

    public function getKeySetAttribute($comboId)
    {
        $keySet = \DB::table('combo_keys')->where('combo_id', $comboId)->pluck('punch_type_id')->toArray();

        return implode('-', $keySet);
    }

    public function getFilterAttribute($comboSetId)
    {
        $filter = \DB::table('combo_set_tags')->select('filter_id')->where('combo_set_id', $comboSetId)->first();

        return (!$filter) ? null : $filter->filter_id;
    }

    public function getRatingAttribute($comboSetId)
    {
        $_rating = \App\Ratings::select(
            \DB::raw('SUM(rating) as sum_of_ratings'),
            \DB::raw('COUNT(rating) as total_ratings')
        )->where('type_id', \App\Types::COMBO_SET)->where('plan_id', $comboSetId)->first();

        $rating = ($_rating->total_ratings > 0) ? ($_rating->sum_of_ratings / $_rating->total_ratings) : 0;
        
        return number_format($rating, 1);
    }
}
