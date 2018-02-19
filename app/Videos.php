<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Videos extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'file',
        'thumbnail',
        'view_counts',
        'duration',
        'author_name'
    ];
    
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

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

        list($width, $height, $type, $attr) = getimagesize(env('STORAGE_URL') . config('striketec.storage.videos_thumb').$thumb);

        return $width;
    }

    public function getThumbHeightAttribute($thumb)
    {
        list($width, $height, $type, $attr) = getimagesize(env('STORAGE_URL') . config('striketec.storage.videos_thumb').$thumb);

        return $height;
    }

    public function RecommendedVideos()
    {
        return $this->belongsTo('App\Videos', 'video_id');
    }

}
