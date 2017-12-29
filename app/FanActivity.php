<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class FanActivity extends Model
{
    protected $fillable = ['name', 'image_url', 'description'];  
    
    public function getStatusAttribute($value) {
        return (bool) $value;
    }
    
    
}
