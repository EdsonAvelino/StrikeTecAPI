<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BattleComboSetCombos extends Model
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

    public function combo()
    {
        return $this->hasOne('App\BattleCombos', 'id', 'battle_combo_id');
    }
}