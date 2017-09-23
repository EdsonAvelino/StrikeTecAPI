<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingSessions extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'training_type_id',
        'start_time',
        'end_time',
        'plan_id',
        'avg_speed',
        'avg_force',
        'punches_count',
        'max_force',
        'max_speed',
    ];

    protected $dateFormat = 'Y-m-d\TH:i:s.u';

    public function rounds()
    {
        return $this->hasMany('App\TrainingSessionRounds', 'training_session_id');
    }

    public function roundsPunches()
    {
        return $this->hasMany('App\TrainingSessionRoundsPunches', 'session_round_id', 'id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $timestamp = $model->start_time / 1000;
            
            $micro = sprintf("%06d", ($timestamp - floor($timestamp)) * 1000000);
            $d = new \DateTime( date('Y-m-d H:i:s.'.$micro, $timestamp) );
            
            $model->created_at = $d->format("Y-m-d H:i:s.u");
        });
    }

    protected function asDateTime($value)
    {
        try {
            return parent::asDateTime($value);
        } catch (\InvalidArgumentException $e) {
            return parent::asDateTime(new \DateTimeImmutable($value));
        }
    }
}