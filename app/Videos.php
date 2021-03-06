<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\StorageHelper;
class Videos extends Model
{
    protected $fillable = [
        'type_id',
        'plan_id',
        'title',
        'file',
        'thumbnail',
        'views',
        'duration',
        'is_featured',
        'author_name',
        'order'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function combo()
    {
        return $this->belongsTo('App\Combos', 'plan_id')->with(['trainer', 'tag']);
    }

    public function comboSet()
    {
        return $this->belongsTo('App\ComboSets', 'plan_id')->with(['trainer', 'tag']);
    }

    public function workout()
    {
        return $this->belongsTo('App\Workouts', 'plan_id')->with(['trainer', 'tag']);
    }

    public function trainer()
    {
        return $this->belongsTo('App\Trainers');
    }

    public function filters()
    {
        return $this->belongsTo('App\VideoTagFilters', 'video_id');
    }

    public function getFilterAttribute($videoId)
    {
        $filter = \DB::table('video_tag_filters')->select('tag_filter_id')->where('video_id', $videoId)->first();

        return (!$filter) ? null : $filter->tag_filter_id;
    }

    // used for essential video
    public function getRatingAttribute($n = null)
    {
        return number_format($n, 1);
    }    

    public function getFileAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

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
            return ($value) ? ( StorageHelper::getFile('videos/'.$value) ) : null;
        }
    }

    public function getThumbnailAttribute($value)
    {

        return ($value) ? ( StorageHelper::getFile('videos/thumbnails/'.$value) ) : null;
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

    public function getUserWatchedVideo($videoId)
    {
        return \App\VideoView::where('user_id', \Auth::id())->where('video_id', $videoId)->first(['watched_count']);
    }
}
