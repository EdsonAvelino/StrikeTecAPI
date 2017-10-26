<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BattleComboSets extends Model
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

    public function combos()
    {
        return $this->hasManyThrough('App\BattleComboSetCombos', 'App\BattleCombos', 'id', 'battle_combo_set_id');
    }
}