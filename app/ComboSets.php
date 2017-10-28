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
}