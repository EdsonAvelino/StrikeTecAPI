<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

Class FanActivity extends Model
{
    protected $fillable = ['name', 'image_url', 'status', 'description'];  
}
