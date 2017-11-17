<?php

namespace App\Http\Controllers\Backend\Videos;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Backend\Videos\VideosRepository;
use App\Http\Requests\Backend\Videos\VideoRequest;

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
    public function upload(){
        $video_cat  = $this->video->catlisting();
        return view('backend.Videos.uploadvideo',['category' => $video_cat]);    
    }
    
    
    /* Listing of all the uploaded videos */
    public function listing(){ 
       $videos = $this->video->listing();
       return view('backend.Videos.allvideos',['videos' => $videos]);
    }
    
    /**
     * Function for register video information 
     * 
     * @param object $request information of video post by user
     * @return type
     */
    public function save(VideoRequest $request){   
        $video_duration = $this->getVideoDuration($_FILES['video_file']['tmp_name']);
        $this->video->save($request, $video_duration);
        $request->session()->flash('Status','Saved successfully!');
        return redirect('admin/videos/listing');
     }
     
    /* edit view for video */
    public function edit($id){
        $video = $this->video->edit($id);
        $video_cat  = $this->video->catlisting();
        return view('backend.Videos.editvideo',['video' => $video['video'],'category' => $video_cat, 'selected_cat' => $video['video_cat']]);
    }
    
    /* deleting a category */
    public function delete(Request $request,$id){
        if($this->video->delete($id)){
            $request->session()->flash('Status','Video deleted successfully!');
            return redirect('admin/videos/listing');
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
        $video_duration = $this->getVideoDuration($_FILES['video_file']['tmp_name']);
        if($this->video->update($request, $id, $video_duration)){
            $request->session()->flash('Status','Video updated successfully!');
            return redirect('admin/videos/listing');
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
