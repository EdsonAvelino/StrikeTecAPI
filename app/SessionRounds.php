<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionRounds extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_id',
        'start_time',
        'end_time',
        'avg_speed',
        'avg_force',
        'punches_count',
        'max_speed',
        'max_force',
        'best_time',
            // 'avg_time',
    ];
    protected $dateFormat = 'Y-m-d\TH:i:s.u';

    public function session()
    {
        return $this->belongsTo('App\TrainingSessions');
    }

    public function punches()
    {
        return $this->hasMany('App\SessionRoundPunches', 'session_round_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $timestamp = $model->start_time / 1000;

            $micro = sprintf("%06d", ($timestamp - floor($timestamp)) * 1000000);
            $d = new \DateTime(date('Y-m-d H:i:s.' . $micro, $timestamp));

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

    public static function getMostPunchesPerMinute($sessionId)
    {
        $avgCount = 0;
        $getAvgCount = SessionRounds::select(
                                \DB::raw('SUM(ABS(start_time - end_time)) AS `total_time`'), \DB::raw('SUM(punches_count) as punches'))
                        ->where('start_time', '>', 0)
                        ->where('end_time', '>', 0)
                        ->where('session_id', $sessionId)->first();
        if ($getAvgCount->total_time > 0) {
            $avgCount = $getAvgCount->punches * 1000 * 60 / $getAvgCount->total_time;
        }
        return $avgCount;
    }

}
