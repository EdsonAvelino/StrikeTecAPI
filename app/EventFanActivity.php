<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class EventFanActivity extends Model
{
    protected $fillable = ['activity_id', 'event_id', 'status'];  
    
    public function getStatusAttribute($value) {
        return (bool) $value;
    }
}
