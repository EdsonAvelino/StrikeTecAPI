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

        $tags = \DB::table('combo_tags')->where('combo_id', $comboId)->pluck('tag_id')->toArray();

        return $tags;
    }

}