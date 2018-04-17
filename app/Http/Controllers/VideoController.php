<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Videos;
use App\UserFavVideos;
use App\Tags;
use App\VideoCategory;

class VideoController extends Controller
{
    /**
     * @api {get} /videos Get videos by category
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} category_id Category Id e.g. 1 = Workout Routines, 2 = Tutorials, 3 = Drills, 4 = Essentials 
     * @apiParam {String} [filter_id] Filter Ids separated by comma e.g. 1,2,3 or just 1
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "category_id": 1,
     *      "filter_id": 1,
     *      "start": 0,
     *      "limit": 10
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} videos List of videos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "videos": [
     *          {
     *              "id": 1,
     *              "title": "Sample Video",
     *              "file": "http://example.com/videos/SampleVideo.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/SampleVideo.png",
     *              "views": 250,
     *              "author_name": "Limer Waughts",
     *              "duration": "00:01:02",
     *              "user_favourited": true,
     *              "thumb_width": 342,
     *              "thumb_height": 185
     *          },
     *          {
     *              "id": 2,
     *              "title": "Another Sample Video",
     *              "file": "http://example.com/videos/video_ScMzIvxBSi4.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/ScMzIvxBSi4.png",
     *              "views": 360,
     *              "author_name": "Aeron Emeatt",
     *              "duration": "00:01:27",
     *              "user_favourited": false,
     *              "thumb_width": 342,
     *              "thumb_height": 185
     *          }
     *      ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
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
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} query Search term e.g. "boxing+stance+and+footwork"
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "query": "boxing+stance+and+footwork",
     *      "start": 0,
     *      "limit": 10,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} videos List of videos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "videos": [
     *          {
     *              "id": 1,
     *              "title": "Sample Video",
     *              "file": "http://example.com/videos/SampleVideo_1280x720_10mb.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/SampleVideo_1280x720_10mb.png",
     *              "views": 250,
     *              "author_name": "Limer Waughts",
     *              "duration": "00:01:02",
     *              "user_favourited": true,
     *              "thumb_width": 342,
     *              "thumb_height": 185
     *          },
     *          {
     *              "id": 2,
     *              "title": "Another Sample Video",
     *              "file": "https://youtu.be/ScMzIvxBSi4",
     *              "thumbnail": "http://example.com/videos/thumb/ScMzIvxBSi4.png",
     *              "views": 360,
     *              "author_name": "Aeron Emeatt",
     *              "duration": "00:01:27",
     *              "user_favourited": false,
     *              "thumb_width": 342,
     *              "thumb_height": 185
     *          },
     *      ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
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

    /**
     * Alter param
     */
    private function alterParam(&$param)
    {
        $param = "%$param%";
    }

    /**
     * @api {post} /videos/favourite/{videoId} Add video to Favourite
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Successfully saved in favourite list",
     *    }
     * @apiErrorExample {json} Error
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
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
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Successfully removed from favourite list",
     *    }
     * @apiErrorExample {json} Error
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
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
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Added successfully",
     *    }
     * @apiErrorExample {json} Error
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function addViewCount($videoId)
    {
        $videoId = (int) $videoId;

        if ($videoId) {
            $video = Videos::find($videoId);

            $video->views = $video->views + 1;
            $video->save();

            return response()->json(['error' => 'false', 'message' => 'Added successfully']);
        }
    }

    /**
     * @api {get} /user/fav_videos Get user's fav videos
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} videos List of videos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *           {
     *             "type_id": 3,
     *             "plan_id": 1,
     *             "title": "Jab-Jab-Cross",
     *             "video_title": "Susan Kocab's Jab-Jab-Cross",
     *             "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523734899.jpg",
     *             "duration": "00:49",
     *             "trainer": {
     *                 "id": 1,
     *                 "type": 1,
     *                 "first_name": "Susan",
     *                 "last_name": "Kocab"
     *             },
     *             "rating": "0.0",
     *             "filter": 1
     *         },
     *         {
     *             "type_id": 3,
     *             "plan_id": 4,
     *             "title": "Jab-Cross-Roll Right",
     *             "video_title": "Susan Kocab's Jab-Cross-Roll Right",
     *             "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523734966.jpg",
     *             "duration": "00:34",
     *             "trainer": {
     *                 "id": 1,
     *                 "type": 1,
     *                 "first_name": "Susan",
     *                 "last_name": "Kocab"
     *             },
     *             "rating": "0.0",
     *             "filter": 1
     *         }
     *      ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
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
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of video's tags
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data":[
     *                      {
     *                          "id": 1,
     *                          "type": 1,
     *                          "name": "Boxing Videos"
     *                      },
     *                      {
     *                          "id": 2,
     *                          "type": 1,
     *                          "name": "Kickboxing Videos"
     *                      }
     *                  ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getVideoTags(Request $request)
    {
        $tagList = Tags::getTags(1);
        return response()->json(['error' => 'false', 'message' => '', 'data' => $tagList]);
    }

    /**
     * @api {get}/videos/category Get list of videos categories
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of videos categories
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data":[
     *                      {
     *                          "id": 1,
     *                          "name": "Workout Routines"
     *                      },
     *                      {
     *                          "id": 2,
     *                          "name": "Tutorials"
     *                      }
     *                      {
     *                          "id": 3,
     *                          "name": "Drills"
     *                      }
     *                      {
     *                          "id": 4,
     *                          "name": "Essentials"
     *                      }
     *                  ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getVideoCategories(Request $request)
    {
        $categories = VideoCategory::all();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $categories]);
    }

    /**
     * @api {get} /tags Get list of tags and filters
     * @apiGroup Videos
     * @apiParam {Number="1-Videos","2-Combos",'3-Workouts','4-Sets'} [type_id] Type Id
     * @apiParamExample {json} Input
     *    {
     *      "type_id": 2
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of tags
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data":[
     *                {
     *                    "id": 1,
     *                    "type": 2,
     *                    "name": "Boxing"
     *                    "filters":
     *                      {
     *                          "id": 1,
     *                          "name": "Beginner"
     *                      },
     *                      {
     *                          "id": 2,
     *                          "name": "Intermediate"
     *                      },
     *                      {
     *                          "id": 3,
     *                          "name": "Advanced"
     *                      }
     *                 },
     *                {
     *                     "id": 2,
     *                     "type": 2,
     *                     "name": "Kickboxing"
     *                     "filters":
     *                      {
     *                          "id": 1,
     *                          "name": "Beginner"
     *                      },
     *                      {
     *                          "id": 2,
     *                          "name": "Intermediate"
     *                      },
     *                      {
     *                          "id": 3,
     *                          "name": "Advanced"
     *                      }
     *               }
     *        ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
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
