<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Videos;
use App\UserFavVideos;
use App\Tags;
use App\VideoCategory;
use App\VideoView;

class VideoController extends Controller
{

    /**
     * @api GET /videos 
     * 
     * Get videos by category
     * 
     * @param Request $request
     *
     * @return json
     */
    public function getVideos(Request $request)
    {
        $categoryId = (int) $request->get('category_id') ? $request->get('category_id') : 0;
        
        $filterId = $request->get('filter_id');
        
        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 20;
        
        $filters = ($filterId) ? explode(',', $filterId) : [];

        $_videos = Videos::select([
                'id',
                'title',
                'file',
                'thumbnail',
                'views',
                'duration',
                'author_name',
                'thumbnail as thumb_width',
                'thumbnail as thumb_height']
            )->where('category_id', $categoryId)->offset($offset)->limit($limit);

        if (count($filters) > 0) {
            $_videos->whereHas('filters', function ($query) use ($filters) {
                $query->whereIn('tag_filter_id',$filters);
            });
        }

        $_videos = $_videos->get();

        $videos = [];
        
        foreach ($_videos as $video) {
            $userFavourited = UserFavVideos::where('user_id', \Auth::user()->id)->where('video_id', $video->id)->exists();
            $video['user_favourited'] = $userFavourited;
            $videos[] = $video;
        }

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos]);
    }

    /**
     * @api {get} /videos/search Search videos
     */
    public function searchVideos(Request $request)
    {
        $query = str_replace('+', ' ', $request->get('query'));

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $queryBreaks = preg_split('/\s+/', $query);
        array_walk($queryBreaks, [$this, 'alterParam']);

        $videos = Videos::select(['*', 'thumbnail as thumb_width', 'thumbnail as thumb_height'])->where('title', 'like', (str_replace('+', '%', $request->get('q'))));

        foreach ($queryBreaks as $queryBreak) {
            $videos->orWhere('title', 'like', $queryBreak);
        }

        $_videos = $videos->offset($offset)->limit($limit)->get();

        $videos = [];

        foreach ($_videos as $video) {
            $userFavourited = UserFavVideos::where('user_id', \Auth::user()->id)->where('video_id', $video->id)->exists();

            $video['user_favourited'] = $userFavourited;

            $videos[] = $video;
        }

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos]);
    }

    public function videosCount(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'featured' => 'sometimes|required|boolean', 
            'my_favorites' => 'sometimes|required|boolean', 
            'is_watched' => 'sometimes|required|boolean',
            'video_length_type' => 'sometimes|required|in:1,2,3,4',
            'skill_level' => 'sometimes|required|in:1,2,3',
            'trainer_id' => 'sometimes|required|exists:trainers,id',
            'sort_by' => 'sometimes|required'
        ]);
 
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $validator->messages()->all()]);
        }

        try {
            $videos = Videos::query()->with(['trainer']);

            // Filter is_featured videos or no
            if ($request->get('featured') !== null) {
                $featured = $request->get('featured');
                $videos = $featured ?  $videos->where('is_featured', true) : $videos->whereNull('is_featured');   
            }

            // Filter auth user favorite videos
            if ($request->get('my_favorites')) {
                $favVideosId = UserFavVideos::where('user_id', \Auth::user()->id)->get(['video_id'])->toArray();
                $videos = $videos->whereIn('id', $favVideosId);   
            }

            // Filter viewed videos or no
            if ($request->get('is_watched') !== null) {
                $isWatched = $request->get('is_watched');

                $userWatched = VideoView::where('user_id', \Auth::user()->id)->get(['video_id']);

                $videos = $isWatched ?  $videos->whereIn('id', $userWatched) : $videos->whereIn('id', '!=', $userWatched);   
            }

            // Filter video duration
            if ($request->get('video_length_type')) {

                $videoLength = $request->get('video_length_type');

                switch ($videoLength) {
                    case 1 :
                        
                        $videos = $videos->where('duration' , '>', '00:00')->where('duration' , '<=', '10:00');   
                        
                        break;
                    case 2 :
                        
                        $videos = $videos->where('duration' , '>', '10:00')->where('duration' , '<=', '20:00');   
                        
                        break;
                    case 3 :
                        
                        $videos = $videos->where('duration' , '>', '20:00')->where('duration' , '<=', '30:00');   
                        
                        break;
                    case 4 :
                        
                        $videos = $videos->where('duration' , '>', '30:00');   
                        
                        break;
                }
            }
            
            // Filter with the skill level
            if ($request->get('skill_level')) {
                
                $skillLevelId = $request->get('skill_level');
                $videos = $videos->where('type_id', $skillLevelId);   
            }

            // Filter with the skill level
            if ($request->get('trainer_id')) {
                
                $trainerId = $request->get('trainer_id');
                $videos = $videos->where('trainer_id', $trainerId);   
            }

            // Filter with the skill level
            if ($request->get('sort_by')) {

                $sortBy = $request->get('sort_by');

                switch ($sortBy) {
                    case 1 :
                        
                        $videos = $videos->orderBy('updated_at', 'DESC');   
                        
                        break;
                    case 2 :
                        
                        $videos = $videos->orderBy('duration', 'DESC');   
                        
                        break;
                    case 3 :
                        
                        $videos = $videos->orderBy('type_id', 'ASC');
                        
                        break;
                }  
            }


            return response()->json(['error' => 'false', 'message' => '', 'data' => ['count' => $videos->count()] ]);
        
        } catch (\Exception $e) {

            return response()->json(['error' => 'true', 'message' => $e->getMessage()]);
        }    
    }

    /**
     * @api {get} /videos/filter
     * 
     */
    public function videosFilter(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'featured' => 'sometimes|required|boolean', 
            'my_favorites' => 'sometimes|required|boolean', 
            'is_watched' => 'sometimes|required|boolean',
            'video_length_type' => 'sometimes|required|in:1,2,3,4',
            'skill_level' => 'sometimes|required|in:1,2,3',
            'trainer_id' => 'sometimes|required|exists:trainers,id',
            'sort_by' => 'sometimes|required',
            'start' => 'sometimes|required',
            'limit' => 'sometimes|required'
        ]);
 
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $validator->messages()->all()]);
        }

        try {
            $videos = Videos::query()->with(['trainer']);

            // Filter is_featured videos or no
            if ($request->get('featured') !== null) {
                $featured = $request->get('featured');
                $videos = $featured ?  $videos->where('is_featured', true) : $videos->whereNull('is_featured');   
            }

            // Filter auth user favorite videos
            if ($request->get('my_favorites')) {
                $favVideosId = UserFavVideos::where('user_id', \Auth::user()->id)->get(['video_id'])->toArray();
                $videos = $videos->whereIn('id', $favVideosId);   
            }

            // Filter viewed videos or no
            if ($request->get('is_watched') !== null) {
                $isWatched = $request->get('is_watched');

                $userWatched = VideoView::where('user_id', \Auth::user()->id)->get(['video_id']);

                $videos = $isWatched ?  $videos->whereIn('id', $userWatched) : $videos->whereIn('id', '!=', $userWatched);   
            }

            // Filter video duration
            if ($request->get('video_length_type')) {

                $videoLength = $request->get('video_length_type');

                switch ($videoLength) {
                    case 1 :
                        
                        $videos = $videos->where('duration' , '>', '00:00')->where('duration' , '<=', '10:00');   
                        
                        break;
                    case 2 :
                        
                        $videos = $videos->where('duration' , '>', '10:00')->where('duration' , '<=', '20:00');   
                        
                        break;
                    case 3 :
                        
                        $videos = $videos->where('duration' , '>', '20:00')->where('duration' , '<=', '30:00');   
                        
                        break;
                    case 4 :
                        
                        $videos = $videos->where('duration' , '>', '30:00');   
                        
                        break;
                }
            }
            
            // Filter with the skill level
            if ($request->get('skill_level')) {
                
                $skillLevelId = $request->get('skill_level');
                $videos = $videos->where('type_id', $skillLevelId);   
            }

            // Filter with the skill level
            if ($request->get('trainer_id')) {
                
                $trainerId = $request->get('trainer_id');
                $videos = $videos->where('trainer_id', $trainerId);   
            }

            // Filter with the skill level
            if ($request->get('sort_by')) {

                $sortBy = $request->get('sort_by');

                switch ($sortBy) {
                    case 1 :
                        
                        $videos = $videos->orderBy('updated_at', 'DESC');   
                        
                        break;
                    case 2 :
                        
                        $videos = $videos->orderBy('duration', 'DESC');   
                        
                        break;
                    case 3 :
                        
                        $videos = $videos->orderBy('type_id', 'ASC');
                        
                        break;
                }  
            }


            // Filter with the skill level
            if ($request->get('start')) {                
                $offset = $request->get('start');
                $videos = $videos->offset($offset);   
            }

            if ($request->get('limit')) {
                $limit = $request->get('limit');
                $videos = $videos->limit($limit);   
            }

            $videoData = $videos->get();
            $responseData = [];

            foreach ($videoData as $key => $value) {

                $responseData[$key]['id'] = $value->id;
                $responseData[$key]['type_id'] = $value->type_id;
                $responseData[$key]['plan_id'] = $value->plan_id;
                $responseData[$key]['title'] = $value->title;
                $responseData[$key]['video_file'] = $value->file;
                $responseData[$key]['video_thumbnail'] = $value->thumbnail;
                $responseData[$key]['duration'] = $value->duration;
                $responseData[$key]['favorite'] = $value->getUserFavoritedAttribute($value->id);
                $responseData[$key]['trainer'] = $value->trainer ? ['id' => $value->trainer->id, 'type' => $value->trainer->type, 'first_name' => $value->trainer->first_name, 'last_name' => $value->trainer->last_name] : false ;
                $responseData[$key]['is_watched'] = $value->getUserWatchedVideo($value->id);
                $responseData[$key]['all_video_views'] = $value->views;
                

            }

            return response()->json(['error' => 'false', 'message' => '', 'data' => $responseData]);
        
        } catch (\Exception $e) {

            return response()->json(['error' => 'true', 'message' => $e->getMessage()]);
        }    
    }

    /**
     * Alter param
     */
    private function alterParam(&$param)
    {
        $param = "%$param%";
    }

    /**
     * @api {post} /videos/favourite/{videoId} Add video to Favourite
     */
    public function setVideoFav($videoId)
    {
        $videoId = (int) $videoId;

        if ($videoId) {
            $userFavVideo = UserFavVideos::firstOrCreate(['user_id' => \Auth::user()->id, 'video_id' => $videoId]);

            return response()->json(['error' => 'false', 'message' => 'Successfully saved in favourite list']);
        }
    }

    /**
     * @api {post} /videos/unfavourite/{videoId} Remove video from Favourite
     */
    public function setVideoUnFav($videoId)
    {
        $videoId = (int) $videoId;

        if ($videoId) {
            UserFavVideos::where('user_id', \Auth::user()->id)->where('video_id', $videoId)->delete();

            return response()->json(['error' => 'false', 'message' => 'Successfully removed from favourite list']);
        }
    }

    /**
     * @api {post} /videos/add_view/{videoId} Add views to video
     * @apiVersion 1.0.0
     */
    public function addViewCount($videoId)
    {
        $videoId = (int) $videoId;

        if ($videoId) {

            $video = Videos::find($videoId);

            $video->views = $video->views + 1;
            $video->save();

            $userWatched = VideoView::where('video_id', $videoId)->where('user_id', \Auth::user()->id)->first();

            if ($userWatched) {
                VideoView::where('video_id', $videoId)->where('user_id', \Auth::user()->id)->update(['watched_count' => $userWatched->watched_count + 1]);
            } else {
                VideoView::create(['user_id' =>  \Auth::user()->id, 'video_id' => $videoId,  'watched_count' => 1]);
            }

            return response()->json(['error' => 'false', 'message' => 'Added successfully']);
        }
    }

    /**
     * @api {get} /user/fav_videos Get user's fav videos
     */
    public function getUserFavVideos(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $videos = Videos::whereRaw("id IN (SELECT video_id from user_fav_videos WHERE user_id = ?)", [\Auth::id()])->offset($offset)->limit($limit)->get();

        $data = [];

        // foreach ($_videos as $video) {
        //     $userFavourited = UserFavVideos::where('user_id', \Auth::id())->where('video_id', $video->id)->exists();

        //     $video['user_favourited'] = $userFavourited;

        //     $planDetails = null;

        //     switch ($video->type_id) {
        //         case \App\Types::COMBO:
        //             $planDetails = \App\Combos::get($video->plan_id);
        //             break;
        //         case \App\Types::COMBO_SET:
        //             $planDetails = \App\ComboSets::get($video->plan_id);
        //             break;
        //         case \App\Types::WORKOUT:
        //             $planDetails = \App\Workouts::getOptimized($video->plan_id);
        //             break;
        //     }

        //     if ($planDetails) {
        //         $video['plan_details'] = $planDetails;
        //     }
            
        //     $videos[] = $video;
        // }

        foreach ($videos as $video) {
            $data[] = $this->getPlanData($video);
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /videos/tags list of video's tags
     */
    public function getVideoTags(Request $request)
    {
        $tagList = Tags::getTags(1);

        return response()->json(['error' => 'false', 'message' => '', 'data' => $tagList]);
    }

    /**
     * @api {get}/videos/category Get list of videos categories
     */
    public function getVideoCategories(Request $request)
    {
        $categories = VideoCategory::all();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $categories]);
    }

    /**
     * @api {get} /trainers Get list of trainers
     */
    public function getTrainers(Request $request)
    {
        $trainers = \App\Trainers::orderBy('type')->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $trainers]);
    }

    /**
     * @api {get} /tags Get list of tags and filters
     */
    public function getTags(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'type_id' => 'nullable|in:1,2,3,4',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors->first('type_id')]);
        }

        $typeId = (int) $request->get('type_id');

        $_tags = Tags::select('*', \DB::raw('1 as filters'));

        if ($typeId) {
            $_tags->where('type', $typeId);
        }
        
        $tags = $_tags->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $tags]);
    }

    /**
     * Getting plan data for /user/fav_videos
     */
    private function getPlanData($video)
    {
        switch ($video->type_id) {
            // Combo
            case \App\Types::COMBO:
                $plan = \App\Combos::select('name', 'trainer_id', \DB::raw('id as rating'), \DB::raw('id as filter'))->where('id', $video->plan_id)->first();
                break;
            
            // Combo Set
            case \App\Types::COMBO_SET:
                $plan = \App\ComboSets::select('name', 'trainer_id', \DB::raw('id as rating'), \DB::raw('id as filter'))->withCount('combos')->where('id', $video->plan_id)->first();
                break;

            // Workout
            case \App\Types::WORKOUT:
                $plan = \App\Workouts::select('name', 'trainer_id', \DB::raw('id as rating'), \DB::raw('id as filter'))->withCount('rounds')->where('id', $video->plan_id)->first();
                break;

            default:
                // Essential Video
                $plan = $video;
                break;
        }
        
        $data = [
            'type_id' => $video->type_id,
            'plan_id' => $video->plan_id,
            'title' => $plan->name,
            'video_title' => $video->title,
            'thumbnail' => $video->thumbnail,
            'duration' => $video->duration,
            'trainer' => ['id' => $plan->trainer->id, 'type' => $plan->trainer->type, 'first_name' => $plan->trainer->first_name, 'last_name' => $plan->trainer->last_name],
            'rating' => $plan->rating,
            'filter' => $plan->filter
        ];

        if ($video->type_id == \App\Types::COMBO_SET) {
            $data['combos_count'] = $plan->combos_count;
        } elseif ($video->type_id == \App\Types::WORKOUT) {
            $data['rounds_count'] = $plan->rounds_count;
        }

        return $data;
    }
}
