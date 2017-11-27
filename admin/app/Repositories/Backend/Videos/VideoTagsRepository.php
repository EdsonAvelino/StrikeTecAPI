<?php

namespace App\Repositories\Backend\Videos;

use Illuminate\Http\Request;
use App\Models\Admin\Video\TaggedVideo;
use App\Repositories\BaseRepository;
/**
 * Class VideoTagsRepository.
 * 
 * @category Videos
 * @package  Videos
 */
class VideoTagsRepository extends BaseRepository
{
    public function save(Request $request)
    {
        $tag_id = $request->video_tag;
        foreach( $tag_id as $val ){
            TaggedVideo::create(['category_id' => $request->cat,
                'video_id' => $request->video_id,
                'tag_id' => $val,
            ]);
        }
        return true;
    }   
    
    public function delete(Request $request)
    {   
        TaggedVideo::where('video_id', $request->video_id)->delete();
        $this->save($request);
    }
}
