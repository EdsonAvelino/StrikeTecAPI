<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class EventFanActivity extends Model
{
    protected $fillable = ['activity_id', 'event_id', 'status'];  
    
     public function getStatusAttribute($value) {
        return (bool) $value;
    }
    
    public function eventSessions() {
        return $this->hasMany('\App\EventSession', 'event_id', 'event_id');
    }

    public function activities() {
        return $this->hasMany('\App\EventSession', 'activity_id', 'activity_id');
    }
}
