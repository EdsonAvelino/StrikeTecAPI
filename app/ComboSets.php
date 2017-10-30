<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComboSets extends Model
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
        return $this->hasManyThrough('App\ComboSetCombos', 'App\Combos', 'id', 'combo_set_id');
    }

    public function getKeySetAttribute($comboId)
    {
        $keySet = \DB::table('combo_keys')->where('combo_id', $comboId)->pluck('punch_type_id')->toArray();

        return implode('-', $keySet);
    }
}