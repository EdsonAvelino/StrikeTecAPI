<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComboSetCombos extends Model
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
        return $this->hasOne('App\Combos', 'id', 'combo_id');
    }
}