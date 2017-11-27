<?php
namespace App\Http\Controllers\Backend\Videos;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Backend\Videos\VideosCategoryRepository;
use App\Http\Requests\Backend\Videos\VideoCategory;

class VideosCategoryController extends Controller
{
     /**
     * @var VideosCategoryRepository
     */
    protected $video_cat;

    /**
     * VideosController constructor.
     *
     * @param VideosRepository $video
     */
    public function __construct(VideosCategoryRepository $video_cat)
    {
        $this->video_cat = $video_cat;
    }
    /* create view for new category */
    public function create(){
        return view('backend.Videos.Category.create');
    }
    /* Listing of all the categories for videos */
    public function listing(){
        $video_cat  = $this->video_cat->listing();
        return view('backend.Videos.Category.listing',['category' => $video_cat]);
    }
    /* edit view for category edit */
    public function edit($id){
        $video_cat = $this->video_cat->edit($id);
        return view('backend.Videos.Category.create',['cat_id' => $id , 'cat_name' => $video_cat]);
    }
    /* deleting a category */
    public function delete(Request $request,$id){
         $this->video_cat->delete($id);
        if($this->video_cat->delete($id)){
            $request->session()->flash('Status','Category deleted successfully!');
            return redirect('admin/categories');
        }
    }
    /* saving a new created category */
    public function save(VideoCategory $request){
        if($this->video_cat->save($request)){
            $request->session()->flash('Status','Category created successfully!');
            return redirect('admin/categories');
        }
    }
    /* updating a existing category */
    public function update(Request $request,$id){
        $this->video_cat->update($request,$id);
        if($this->video_cat->update($request,$id)){
            $request->session()->flash('Status','Category updated successfully!');
            return redirect('admin/categories');
        }
    }
}