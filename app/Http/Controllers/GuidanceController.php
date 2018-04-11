<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuidanceController extends Controller
{
	/**
     * @api {get} /guidance/home Guidance home screen
     * @apiGroup Guidance
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} [query] Search term e.g. "boxing+stance+and+footwork"
     * @apiParamExample {json} Input
     *    {
     *      "query": "susan+kokab",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Data object
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
    *              "featured": [
     *                  {
     *                      "type_id": 3,
     *                      "data": "{\"plan_id\":1,\"title\":\"Jab-Jab-Cross\",\"video_title\":\"Susan Kocab's Jab-Jab-Cross\",\"thumbnail\":\"http:\\/\\/localhost:8001\\/videos\\/thumbnails\\/thumb_video_1523057864.png\",\"duration\":\"00:00:30\",\"trainer\":null,\"rating\":\"0.0\"}"
     *                  },
     *                  {
     *                      "type_id": 4,
     *                      "data": "{\"plan_id\":1,\"title\":\"Sample SR-1\",\"video_title\":\"Sample SR-1\",\"thumbnail\":\"http:\\/\\/localhost:8001\\/videos\\/thumbnails\\/thumb_SampleVideo_1280x720_5mb.png\",\"duration\":\"00:00:13\",\"trainer\":null,\"rating\":\"0.0\"}"
     *                  },
     *                  {
     *                      "type_id": 5,
     *                      "data": "{\"plan_id\":1,\"title\":\"BR1\",\"video_title\":\"Sample Boxing Routine-11\",\"thumbnail\":\"http:\\/\\/localhost:8001\\/videos\\/thumbnails\\/thumb_SampleVideo_1280x720_5mb.png\",\"duration\":\"00:00:04\",\"trainer\":null,\"rating\":\"0.0\"}"
     *                  }
     *              ],
     *              "combinations": [
     *                  {
     *                      "type_id": 3,
     *                      "data": "{\"plan_id\":3,\"title\":\"Jab-Cross-Left Hook\",\"video_title\":\"Susan Kocab's Jab-Cross-Left Hook (1-2-3)\",\"thumbnail\":\"http:\\/\\/localhost:8001\\/videos\\/thumbnails\\/thumb_video_1523023487.jpg\",\"duration\":\"00:01:05\",\"trainer\":null,\"rating\":\"0.0\"}"
     *                  },
     *                  {
     *                      "type_id": 3,
     *                      "data": "{\"plan_id\":2,\"title\":\"Jab-Cross\",\"video_title\":\"Susan Kocab's Jab-Cross\",\"thumbnail\":\"http:\\/\\/localhost:8001\\/videos\\/thumbnails\\/thumb_video_1523023401.jpg\",\"duration\":\"00:00:30\",\"trainer\":null,\"rating\":\"0.0\"}"
     *                  }
     *              ],
     *              "sets": [
     *                  {
     *                      "type_id": 4,
     *                      "data": "{\"plan_id\":1,\"title\":\"Sample SR-1\",\"video_title\":\"Sample SR-1\",\"thumbnail\":\"http:\\/\\/localhost:8001\\/videos\\/thumbnails\\/thumb_SampleVideo_1280x720_5mb.png\",\"duration\":\"00:00:13\",\"trainer\":null,\"rating\":\"0.0\"}"
     *                  }
     *              ],
     *              "workouts": [
     *                  {
     *                      "type_id": 5,
     *                      "data": "{\"plan_id\":1,\"title\":\"BR1\",\"video_title\":\"Sample Boxing Routine-11\",\"thumbnail\":\"http:\\/\\/localhost:8001\\/videos\\/thumbnails\\/thumb_SampleVideo_1280x720_5mb.png\",\"duration\":\"00:00:04\",\"trainer\":null,\"rating\":\"0.0\"}"
     *                  }
     *              ],
     *              "essentials": [
     *                  {
     *                      "type_id": 0,
     *                      "data": "{\"id\":25,\"type_id\":null,\"plan_id\":null,\"title\":\"Essential Vid-I\",\"file\":\"http:\\/\\/localhost:8001\\/videos\\/video_1511264605.mp4\",\"thumbnail\":\"http:\\/\\/localhost:8001\\/videos\\/thumbnails\\/thumb_SampleVideo_1280x720_5mb.png\",\"duration\":\"00:00:04\",\"views\":1,\"is_featured\":false,\"user_favorited\":false,\"likes\":0}"
     *                  }
     *              ]
     *		],
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */

    public function home(Request $request)
    {
    	$data = [];

        $trainer = null;

        if (!empty($request->get('query'))) {
            $searchQuery = $request->get('query');

            $_trainer = \App\NewTrainers::select('id')->where(function ($q) use ($searchQuery) {
                $name = explode(' ', str_replace('+', ' ', $searchQuery));
                    if (count($name) > 1) {
                        $q->where('first_name', 'like', "%$name[0]%")->orWhere('last_name', 'like', "%$name[1]%");
                    } else {
                        $q->where('first_name', 'like', "%$name[0]%")->orWhere('last_name', 'like', "%$name[0]%");
                    }
                });

            $trainer = $_trainer->first();
        }

        // Featured videos
    	$featuredItems = \App\GuidanceSlider::orderBy('order')->limit(5)->get();

        $featuredData = [];

        foreach ($featuredItems as $item) {
            $video = \App\NewVideos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))->where('type_id', $item->type_id)->where('plan_id', $item->plan_id)->first();
            
            $data['featured'][] = $this->getPlanData($video);
        }

        // Combos
    	$_comboVideos = \App\NewVideos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))
            ->where('is_featured', 1)->where('type_id', \App\Types::COMBO)
            ->orderBy('views', 'desc')->orderBy('likes', 'desc')->limit(5);

        if ($trainer) {
            $_comboVideos->whereHas('combo', function($query) use($trainer) {
                $query->where('trainer_id', $trainer->id);
            });
        }
    	
        $comboVideos = $_comboVideos->get();

    	foreach ($comboVideos as $comboVideo) {
    		$data['combinations'][] = $this->getPlanData($comboVideo);
    	}

        // Combo-Sets
    	$_comboSetVideos = \App\NewVideos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))
            ->where('is_featured', 1)->where('type_id', \App\Types::COMBO_SET)
            ->orderBy('views', 'desc')->orderBy('likes', 'desc')->limit(5);
    	
        if ($trainer) {
            $_comboSetVideos->whereHas('comboSet', function($query) use($trainer) {
                $query->where('trainer_id', $trainer->id);
            });
        }

        $comboSetVideos = $_comboSetVideos->get();

        foreach ($comboSetVideos as $comboSetVideo) {
    		$data['sets'][] = $this->getPlanData($comboSetVideo);
    	}

        // Workouts
    	$_workoutVideos = \App\NewVideos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))
            ->where('is_featured', 1)->where('type_id', \App\Types::WORKOUT)
            ->orderBy('views', 'desc')->orderBy('likes', 'desc')->limit(5);
    	
        if ($trainer) {
            $_workoutVideos->whereHas('workout', function($query) use($trainer) {
                $query->where('trainer_id', $trainer->id);
            });
        }

        $workoutVideos = $_workoutVideos->get();

        foreach ($workoutVideos as $workoutVideo) {
    		$data['workouts'][] = $this->getPlanData($workoutVideo);
    	}

        // Essentials
        $essentialVideos = \App\NewVideos::select('*', \DB::raw('id as plan_id'), \DB::raw('title as name'), \DB::raw('id as user_favorited'), \DB::raw('id as likes'))
            ->where(function($query) {
                $query->whereNull('type_id')->orWhere('type_id', 0);
            })->limit(5)->get();

        foreach ($essentialVideos as $essentialVideo) {
            $data['essentials'][] = $this->getPlanData($essentialVideo);
            // ['type_id' => 0, 'data' => json_encode($essentialVideo)]
        }

	    return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /guidance/plans/<type_id> Guidance list of plans
     * @apiGroup Guidance
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number="3=combos", "4=combo-sets", "5=workouts"} type_id Type of plan (in url param)
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "type_id": 4,
     *      "start": 0,
     *      "limit": 20
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Data object containing list of plans
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *              "type_id": 3,
     *              "data": "{\"plan_id\":1,\"title\":\"Jab-Jab-Cross\",\"video_title\":\"Susan Kocab's Jab-Jab-Cross\",\"thumbnail\":\"http:\\/\\/videos.example.com\\/videos\\/thumbnails\\/thumb_video_1523057864.png\",\"duration\":\"00:00:30\",\"trainer\":null,\"rating\":\"3.5\"}"
     *          },
     *          {
     *              "type_id": 3,
     *              "data": "{\"plan_id\":2,\"title\":\"Jab-Cross\",\"video_title\":\"Susan Kocab's Jab-Cross\",\"thumbnail\":\"http:\\/\\/videos.example.com\\/videos\\/thumbnails\\/thumb_video_1523023401.jpg\",\"duration\":\"00:00:30\",\"trainer\":null,\"rating\":\"4.0\"}"
     *          }
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getPlans(Request $request, $typeId)
    {
        if (!in_array($typeId, [\App\Types::COMBO, \App\Types::COMBO_SET, \App\Types::WORKOUT])) {
            return response()->json(['error' => 'true', 'message' => 'Invalid type-id, should be 3, 4 or 5 respectively']);
        }

        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 10;

        $trainer = null;

        if (!empty($request->get('query'))) {
            $searchQuery = $request->get('query');

            $_trainer = \App\NewTrainers::select('id')->where(function ($q) use ($searchQuery) {
                $name = explode(' ', str_replace('+', ' ', $searchQuery));
                    if (count($name) > 1) {
                        $q->where('first_name', 'like', "%$name[0]%")->orWhere('last_name', 'like', "%$name[1]%");
                    } else {
                        $q->where('first_name', 'like', "%$name[0]%")->orWhere('last_name', 'like', "%$name[0]%");
                    }
                });

            $trainer = $_trainer->first();
        }

        $_planVideos = \App\NewVideos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))->where('type_id', $typeId)->offset($offset)->limit($limit);

        if ($trainer) {
            $_planVideos->whereHas('combo', function($query) use($trainer) {
                $query->where('trainer_id', $trainer->id);
            });
        }

        $planVideos = $_planVideos->get();

        $data = [];

        foreach ($planVideos as $planVideo) {
            $data[] = $this->getPlanData($planVideo);
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /guidance/plans/<type_id>/<plan_id> Guidance detail of plan
     * @apiGroup Guidance
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number="3=combos", "4=combo-sets", "5=workouts"} type_id Type of plan (in url param)
     * @apiParam {Number} plan_id ID of plan (combo / set-routine / workout)
     * @apiParamExample {json} Input
     *    {
     *      "type_id": 3,
     *      "plan_id": 5,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Data object containing detail of particular plan
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *              "type_id": 3,
     *              "data": "{\"plan_id\":5,\"title\":\"Jab- Roll Left\",\"video_title\":\"Susan Kocab's Jab-Roll Left\",\"thumbnail\":\"http:\\/\\/videos.example.com\\/videos\\/thumbnails\\/thumb_video_1523024274.jpg\",\"duration\":\"00:00:24\",\"trainer\":null,\"rating\":\"4.1\"}"
     *          }
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getPlanDetail(Request $request, $typeId, $planId)
    {
        if (!in_array($typeId, [\App\Types::COMBO, \App\Types::COMBO_SET, \App\Types::WORKOUT])) {
            return response()->json(['error' => 'true', 'message' => 'Invalid type-id, should be 3, 4 or 5 respectively']);
        }

        if (!$planId) {
            return response()->json(['error' => 'true', 'message' => 'Invalid plan-id or plan not found']);
        }

        // $planVideo = \App\NewVideos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))->where('type_id', $typeId)->where('plan_id', $planId)->first();

        switch ($typeId) {
            case \App\Types::COMBO:
                $plan = \App\NewCombos::get($planId);
                break;
            case \App\Types::COMBO_SET:
                $plan = \App\NewComboSets::get($planId);
                break;
            case \App\Types::WORKOUT:
                $plan = \App\NewWorkouts::get($planId);
                break;
        }

        $data = ['type_id' => (int) $typeId, 'data' => json_encode($plan)];

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {post} /guidance/rate Rate combo, set or workout
     * @apiGroup Guidance
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number="1=Combo", "2=Combo-set", "3-Workout"} type_id Type ID
     * @apiParam {Number} plan_id ID of combo/set/workout respectively 
     * @apiParam {Number} rating Rating value 1 to 5
     * @apiParamExample {json} Input
     *    {
     *      "type_id": 1,
     *      "plan_id": 3,
     *      "rating": 5,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Data object
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Rating saved",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message / Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function postRating(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'type_id' => 'required|integer|in:3,4,5',
            'plan_id' => 'required|integer',
            'rating' => 'required|integer|between:1,5',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->get('type_id')) {
                return response()->json(['error' => 'true', 'message' => $errors->first('type_id')]);
            } else {
                return response()->json(['error' => 'true', 'message' => $errors->first('rating')]);
            }
        }

        $typeId = (int) $request->get('type_id');
        $planId = (int) $request->get('plan_id');
        $rating = (int) $request->get('rating');

        $ratingExists = \App\NewRatings::where('user_id', \Auth::id())->where('type_id', $typeId)->where('plan_id', $planId)->exists();

        if (!$ratingExists) {
            \App\NewRatings::create([
                'user_id' => \Auth::id(),
                'type_id' => $typeId,
                'plan_id' => $planId,
                'rating' => $rating
            ]);
        }

        return response()->json(['error' => 'false', 'message' => 'Rating saved']);
    }

    /**
     * @api {get} /guidance/essentials Get list of Essentials Videos
     * @apiGroup Guidance
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "start": 0,
     *      "limit": 20
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Data object containing list of essentials videos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getEssentialsVideos(Request $request)
    {
        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 10;

        // Essentials
        $essentialVideos = \App\NewVideos::select('*', \DB::raw('id as user_favorited'), \DB::raw('id as likes'))
            ->where(function($query) {
                $query->whereNull('type_id')->orWhere('type_id', 0);
            })->offset($offset)->limit($limit)->get();

        $data = [];
        
        foreach ($essentialVideos as $essentialVideo) {
            $data[] = ['type_id' => 0, 'data' => json_encode($essentialVideo)];
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /guidance/essentials/<id> Get Essentials Video Detail
     * @apiGroup Guidance
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} id Id of essential video
     * @apiParamExample {json} Input
     *    {
     *      "id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Data object containing detial essential video which is requested
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getEssentialsVideoDetail(Request $request, $id)
    { 
        $id = (int) $id;

        $essentialVideo = \App\NewVideos::select('*', \DB::raw('id as user_favorited'), \DB::raw('id as likes'))
            ->where(function($query) {
                $query->whereNull('type_id')->orWhere('type_id', 0);
            })->where('id', $id)->first();

        if (!$essentialVideo) {
            return response()->json(['error' => 'true', 'message' => 'Invalid request or video not found']);
        }
        
        $data = ['type_id' => 0, 'data' => json_encode($essentialVideo)];

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * Getting plan data for /guidance/home (optimized object)
     */
    private function getPlanData($video)
    {
        switch ($video->type_id) {
            // Combo
            case \App\Types::COMBO:
                $plan = \App\NewCombos::select('name', 'trainer_id', \DB::raw('id as rating'))->where('id', $video->plan_id)->first();
                break;
            
            // Combo Set
            case \App\Types::COMBO_SET:
                $plan = \App\NewComboSets::select('name', 'trainer_id', \DB::raw('id as rating'))->withCount('combos')->where('id', $video->plan_id)->first();
                break;

            // Workout
            case \App\Types::WORKOUT:
                $plan = \App\NewWorkouts::select('name', 'trainer_id', \DB::raw('id as rating'))->withCount('rounds')->where('id', $video->plan_id)->first();
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
            'trainer' => ['id' => $plan->trainer->id, 'first_name' => $plan->trainer->first_name, 'last_name' => $plan->trainer->last_name],
            'rating' => $plan->rating
        ];

        if ($video->type_id == \App\Types::COMBO_SET) {
            $data['combos_count'] = $plan->combos_count;
        } elseif ($video->type_id == \App\Types::WORKOUT) {
            $data['rounds_count'] = $plan->rounds_count;
        }

        return $data;
    }

    /**
     * Alter param
     */
    private function alterParam(&$param)
    {
        $param = "%$param%";
    }
}