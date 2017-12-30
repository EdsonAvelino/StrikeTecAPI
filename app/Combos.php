<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Combos extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

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

    public function getTagsAttribute($comboId)
    {
        $comboId = (int) $comboId;

        if (empty($comboId)) {
            return null;
        }

        $tagFilters = [];
        $tags = \DB::table('combo_tags')->select('tag_id', 'filter_id')->where('combo_id', $comboId)->get();
        foreach ($tags as $tag) {
            $tagFilters[$tag->tag_id]['tag_id'] = $tag->tag_id;

            $tagFilters[$tag->tag_id]['filters'][] = $tag->filter_id;
        }

        return array_values($tagFilters);
    }

    public function videos()
    {
        return $this->hasOne('App\ComboVideos', 'combo_id');
    }

}
