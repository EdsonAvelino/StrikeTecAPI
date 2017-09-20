<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingSessionRoundsPunches extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_round_id',
        'punch_time',
        'punch_duration',
        'force',
        'speed',
        'punch_type',
        'hand',
    ];

    protected $dateFormat = 'Y-m-d H:i:s.u';
    
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $timestamp = $model->punch_time / 1000;
            
            $micro = sprintf("%06d",($timestamp - floor($timestamp)) * 1000000);
            $d = new \DateTime( date('Y-m-d H:i:s.'.$micro, $timestamp) );
            
            $model->created_at = $d->format("Y-m-d H:i:s.u");
        });
    }
}