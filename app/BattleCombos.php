<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BattleCombos extends Model
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

    // public function keySet()
    // {
    //     return $this->hasMany('App\BattleComboKeys', 'battle_combo_id');
    // }

    public function getKeySetAttribute($comboId)
    {
        $keySet = \DB::table('battle_combo_keys')->where('battle_combo_id', $comboId)->pluck('punch_type_id')->toArray();

        return implode('-', $keySet);
    }
}