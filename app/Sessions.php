<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sessions extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'battle_id',
        'type_id',
        'start_time',
        'end_time',
        'plan_id',
        'avg_speed',
        'avg_force',
        'punches_count',
        'max_force',
        'max_speed',
        'best_time',
    ];

    protected $dateFormat = 'Y-m-d\TH:i:s.u';

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }
    
    public function rounds()
    {
        return $this->hasMany('App\SessionRounds', 'session_id');
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

    public function getSharedAttribute($shared)
    {
        $shared = filter_var($shared, FILTER_VALIDATE_BOOLEAN);
        return ($shared) ? 'true' : 'false';
    }
}