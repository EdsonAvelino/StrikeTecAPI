<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewVideos extends Model
{
    protected $table = '__videos';

    protected $fillable = [
        'type_id',
        'plan_id',
        'title',
        'file',
        'thumbnail',
        'views',
        'duration',
        'is_featured',
        'author_name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function filters()
    {
        return $this->hasMany('App\VideoTagFilters', 'video_id');
    }

    public function getFileAttribute($value)
    {
        if (strpos($value, 'youtube') > 0 || strpos($value, 'youtu.be') > 0) {
            $youtubeUrl = $value;

            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $youtubeUrl, $match);

            $videoId = $match[1];

            $videoFormat = 'video/mp4'; // The MIME type of the video. e.g. video/mp4, video/webm, etc.

            parse_str(file_get_contents("http://youtube.com/get_video_info?video_id=" . $videoId), $info);

            $streams = $info['url_encoded_fmt_stream_map'];
            $streams = explode(',', $streams);

            foreach ($streams as $stream) {
                parse_str($stream, $data); //Now decode the stream

                if (stripos($data['type'], $videoFormat) !== false) {
                    return $data['url'];
                }
            }
        } else {
            return env('STORAGE_URL') . config('striketec.storage.videos') . $value;
        }
    }

    public function getThumbnailAttribute($value)
    {
        return env('STORAGE_URL') . config('striketec.storage.videos_thumb') . $value;
    }

    public function getThumbWidthAttribute($thumb)
    {
        $thumbFilePath = base_path('../storage'.(config('striketec.storage.videos_thumb')).$thumb);

        if (!file_exists($thumbFilePath))
            return 0;

        list($width, $height, $type, $attr) = getimagesize($thumbFilePath);

        return $width;
    }

    public function getThumbHeightAttribute($thumb)
    {
        $thumbFilePath = base_path('../storage'.(config('striketec.storage.videos_thumb')).$thumb);

        if (!file_exists($thumbFilePath))
            return 0;
        
        list($width, $height, $type, $attr) = getimagesize($thumbFilePath);

        return $height;
    }

    public function getUserFavoritedAttribute($videoId)
    {
        return (bool) \App\UserFavVideos::where('user_id', \Auth::id())->where('video_id', $videoId)->exists();
    }

    public function getLikesAttribute($videoId)
    {
        return (int) \App\UserFavVideos::where('video_id', $videoId)->count();
    }

    public function getIsFeaturedAttribute($isFeatured)
    {
        return (bool) $isFeatured;
    }
}
