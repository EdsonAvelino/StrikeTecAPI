<?php

namespace App\Repositories\Backend\Videos;

use App\Models\Admin\Video\Video;
use App\Models\Admin\Video\VideoCategory;
use App\Models\Admin\Video\TaggedVideo;
use App\Repositories\BaseRepository;
/**
 * Class VideosRepository.
 * 
 * @category Videos
 * @package  Videos
 */
class VideosRepository extends BaseRepository
{
    protected $apiStoragePath = '../../storage';
    /**
     * Listing of videos
    */
     
    public function listing()
    {
        $video_cat= new VideoCategory;
        $result = $video_cat->rightJoin('videos', 'video_categories.id', '=', 'videos.category_id')->get();
        return $result;
    }
    
    
    /* saving categories listed for category */
    public function catlisting()
    {
        $video_cat = new VideoCategory;
        $cats = $video_cat::all();
        return $cats;
    }
    
    /* saving video */
    public function save($request, $video_duration) {
        $created_time=date('Y-m-d h:i:s');
        $video_name = 'video_'.time().'.'.$request->video_file->getClientOriginalExtension();
        $video_thumb_name = 'video_thumb_'.time().'.'.$request->video_thumbnail->getClientOriginalExtension();
        $request->video_file->move($this->apiStoragePath . '/videos/', $video_name);
        $request->video_thumbnail->move($this->apiStoragePath . '/videos/thumbnails/', $video_thumb_name);
        $video = new Video;
        $video_id = $video->create(['category_id' => $request->cat,
                    'title' => $request->title,
                    'file' => $video_name,
                    'price' => $request->price,
                    'duration' => $video_duration,
                    'thumbnail' => $video_thumb_name,
                    'author_name' => $request->author_name,
                    'created_at' => $created_time,
                    'updated_at' => $created_time
                ]);
        return $video_id->id;
    }
    
    /* editing video */
    public function edit($id){
        $video = new Video;
        $video = $video->where('id',$id)->first();
        $video['video'] = $video->where('id',$id)->first();
        $video_cat = new VideoCategory;
        $video['video_cat'] = $video_cat->where('id', $video->category_id)->first()->name;
        $ObjTaggedVideo = new TaggedVideo;
        $video['tagged_video'] = $ObjTaggedVideo->where('video_id', $id)->select('tag_id')->get();
        return $video;
    }
    
    /* deleting a video */
    public function delete($id){
        $this->unlinkFile('both', $id);
        $video = new Video;
        $video->where('id', $id)->delete();
        return true;
    }
    
   /**
     *  Function for update information
     * 
     *  @param object, integer, string $request, $id, $video_duration
     *  @return boolean
    */
    public function update($request, $id, $video_duration) {
        $updated_time = date('Y-m-d h:i:s');
        $video= new Video;
        $video->where('id', $id)->update(['category_id' => $request->cat,
                    'title' => $request->title,
                    'author_name' => $request->author_name,
                    'price' => $request->price,
                    'updated_at' => $updated_time
                ]);
        if(isset($request->video_file)){
            $this->unlinkFile('video', $id);
            $video_name = 'video_'.time().'.'.$request->video_file->getClientOriginalExtension();
            $request->video_file->move($this->apiStoragePath.'/videos/', $video_name);
            $video->where('id', $id)->update([
                    'file' => $video_name,
                    'updated_at' => $updated_time,
                    'duration' => $video_duration
                ]);
        }
        if(isset($request->video_thumbnail)){
            $this->unlinkFile('thumb', $id);
            $video_thumb_name = 'video_thumb_'.time().'.'.$request->video_thumbnail->getClientOriginalExtension();
            $request->video_thumbnail->move($this->apiStoragePath.'/videos/thumbnails/', $video_thumb_name);
            $video->where('id', $id)->update([
                    'thumbnail' => $video_thumb_name,
                    'updated_at' => $updated_time
                ]);
        }
        return true;
    }
    
    /**
     * Function for unlink file into folder
     * 
     * @param string $identity identity for which one file you want to remove from folder.
     * @param integer $id identity for which id you can get information.
     * @return NULL
     */
    function unlinkFile($identity, $id) {
        $video_information = Video::where('id', $id)->first();
        $video_file = $this->apiStoragePath.'/videos/'.$video_information->file ;
        $thumbnail_file = $this->apiStoragePath.'/videos/thumbnails/'.$video_information->thumbnail;
        if(($identity == 'video' || $identity == 'both') && file_exists( $video_file)){
            
            /* Start unlink old video from path */
                unlink(public_path($video_file));
            /* End unlink old video form path */
        }
        if(($identity == 'thumb' || $identity == 'both') && file_exists( $thumbnail_file)){
            /* Start unlink old thumnail image from path */
                unlink(public_path($thumbnail_file));
            /* End unlink old thumnail image form path */
        }return;
    }     
        
}
