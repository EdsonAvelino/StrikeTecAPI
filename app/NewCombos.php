<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewCombos extends Model
{
    protected $table = '__combos';

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
}
