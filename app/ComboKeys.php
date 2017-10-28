<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComboKeys extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'combo_id',
        'punch_type_id'
    ];

    public $timestamps = false;

    public function punchType()
    {
        return $this->belongsTo('App\PunchTypes');
    }
}