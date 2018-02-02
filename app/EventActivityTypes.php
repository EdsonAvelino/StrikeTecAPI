<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class EventActivityTypes extends Model
{
    protected $fillable = ['name', 'image_url', 'description'];

    public function getImageUrlAttribute($image)
    {
    	if ($image) {
            return env('APP_URL') . '/storage/events/activities/' . $image;
        }
    }
}