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
        'description'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function keySet()
    {
        return $this->hasMany('App\BattleComboKeys', 'battle_combo_id');
    }
}