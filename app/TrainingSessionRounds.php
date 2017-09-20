<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingSessionRounds extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'training_session_id',
        'start_time',
        'end_time',
        'avg_speed',
        'avg_force',
        'punches_count',
        'max_speed',
        'max_force',
        'best_time',
        'avg_time',
    ];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public function session()
    {
        return $this->belongsTo('App\TrainingSessions');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $timestamp = $model->start_time / 1000;
            
            $micro = sprintf("%06d",($timestamp - floor($timestamp)) * 1000000);
            $d = new \DateTime( date('Y-m-d H:i:s.'.$micro, $timestamp) );
            
            $model->created_at = $d->format("Y-m-d H:i:s.u");
        });
    }
}