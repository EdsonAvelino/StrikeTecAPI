<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class EventActivityTypes extends Model
{
    protected $fillable = ['name', 'image_url', 'description'];
}