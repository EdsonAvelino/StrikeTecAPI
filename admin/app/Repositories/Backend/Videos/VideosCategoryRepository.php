<?php

namespace App\Repositories\Backend\Videos;

use App\Models\Admin\Video\VideoCategory;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;

/**
 * Class VideoCategoryRepository.
 * 
 * @category VideoCategory
 * @package  VideoCategory
 */
class VideosCategoryRepository extends BaseRepository
{
   /* listing of all the video categories */
    public function listing(){
        $video_cat = new VideoCategory;
        $cats = $video_cat::all();
        return $cats;
    }
    /* editing video category */
    public function edit($id){
        $video_cat = new VideoCategory;
        $cat = $video_cat->where('id',$id)->first();
        return $cat->name;
    }
    /* deleting a category */
    public function delete($id){
        $video_cat = new VideoCategory;
        $video_cat->where('id', $id)->delete();
        return true;
    }
    /* saving a new created category */
    public function save($request){
        $video_cat = new VideoCategory;
        $video_cat->insert(['name' => $request->name]);
        return true;
    }
    /* updating a existing category */
    public function update($request,$id){
        DB::table('video_categories')->where('id', $id)->update(['name' => $request->name]);
        return true;
    }
}
