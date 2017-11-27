<?php

namespace App\Http\Controllers\Backend\Videos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Backend\Videos\VideosRepository;
use App\Http\Requests\Backend\Videos\VideoRequest;
use App\Repositories\Backend\Videos\TagsRepository;
use App\Repositories\Backend\Videos\VideoTagsRepository;
use Validator;
use Session;

class VideosController extends Controller
{
    /**
     * @var VideosRepository
     */
    protected $video;

    /**
     * VideosController constructor.
     *
     * @param VideosRepository $video
     */
    public function __construct(VideosRepository $video)
    {
        $this->video = $video;
    }
    
    
   /* Uploading a new video UI */
    public function upload($id = null){  
        if($id) {
            $video = $this->video->edit($id);
            $video_cat  = $this->video->catlisting();//return $video->tagged_video;
            $objTagRepository = new TagsRepository();
            $videoTagList = $objTagRepository->listing();
            return view('backend.Videos.editvideo', ['video' => $video['video'], 'tagged_video' => $video['tagged_video'], 'category' => $video_cat, 'selected_cat' => $video['video_cat'], 'videoTagList' => $videoTagList]);
        }   
        $objTagRepository = new TagsRepository();
        $videoTagList = $objTagRepository->listing();
        $video_cat  = $this->video->catlisting();
        return view('backend.Videos.uploadvideo', ['category' => $video_cat , 'videoTagList' => $videoTagList]);    
    }
    
    
    /* Listing of all the uploaded videos */
    public function listing(){
       $videos = $this->video->listing();
       return view('backend.Videos.allvideos', ['videos' => $videos]);
    }
    
    /**
     * Function for register video information 
     * 
     * @param object $request information of video post by user
     * @return type
     */
    public function save(VideoRequest $request){   
        $video_duration = $this->getVideoDuration($_FILES['video_file']['tmp_name']);
        $videoID = $this->video->save($request, $video_duration);
        if(isset($request->video_tag)){
            $videoTagStorage = array ('video_id' => $videoID);
            $request->merge($videoTagStorage);
            $objVideoTagRepo = new VideoTagsRepository();
            $objVideoTagRepo->save($request);
        }
       $request->session()->flash('Status','Video saved successfully!');
        return redirect()->route('admin.videos.list'); 
     }
     
    /* edit view for video */
    /*public function edit($id){
        $video = $this->video->edit($id);
        $video_cat  = $this->video->catlisting();
        return view('backend.Videos.editvideo',['video' => $video['video'],'category' => $video_cat, 'selected_cat' => $video['video_cat']]);
    }*/
    
    /* deleting a category */
    public function delete(Request $request,$id){
        if($this->video->delete($id)){
            $request->session()->flash('Status','Video deleted successfully!');
            return redirect()->route('admin.videos.list'); 
        }
    }
    
    /**
     * Function for updating information
     * 
     * @param Request $request
     * @param integer $id
     * @return NULL
     */
    public function update(Request $request, $id){
        $video_duration = '';
        if($_FILES['video_file']['tmp_name']) { 
           // $data = $request->input();
            $rules = array(
                'video_file' => 'required|mimes:mp4|max:50000',
            );
            $validate = Validator::make($request->all(), $rules);
            if($validate->fails()) {
                Session::flash('error_message', 'Video format or size are not valid' );
                return redirect()->back();
            }
            $video_duration = $this->getVideoDuration($_FILES['video_file']['tmp_name']);
        } 
        if( $_FILES['video_thumbnail']['tmp_name'] ) {  
            $rules = array(
                'video_thumbnail'=> 'required|mimes:jpeg,jpg,png,ico',
            );
            $validate = Validator::make($request->all(), $rules);
            if($validate->fails()) {
                Session::flash('error_message', 'Thumbnail file or format are not valid' );
                return redirect()->back();
            }
        } 
        if($this->video->update($request, $id, $video_duration)){
            if(isset($request->video_tag)){
                $videoTagStorage = array ('video_id' => $id);
                $request->merge($videoTagStorage);
                $objVideoTagRepo = new VideoTagsRepository();
                $objVideoTagRepo->delete($request);
            }
            $request->session()->flash('Status','Video updated successfully!');
            return redirect()->route('admin.videos.list'); 
        }
    }
    
    /**
     *  Function for get uploaded video duration 
     * 
     *  @param string $full_video_path full path of uploaded video
     *  @return string video duration of uploaded video
    */
    public function getVideoDuration($full_video_path)
    {
        $getID3 = new \getID3;
        $file = $getID3->analyze($full_video_path);
        $playtime_seconds = $file['playtime_seconds'];
        $duration = date('H:i:s', $playtime_seconds);
        return $duration;
    }
}
