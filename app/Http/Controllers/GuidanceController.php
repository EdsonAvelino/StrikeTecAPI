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
     *          "featured": [
     *             {
     *                 "type_id": 3,
     *                 "plan_id": 2,
     *                 "title": "Jab-Cross",
     *                 "video_title": "Susan Kocab's Jab-Cross",
     *                 "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523734934.jpg",
     *                 "duration": "00:30",
     *                 "trainer": {
     *                     "id": 1,
     *                     "type": 1,
     *                     "first_name": "Susan",
     *                     "last_name": "Kocab"
     *                 },
     *                 "rating": "5.0",
     *                 "filter": 1
     *             },
     *             {
     *                 "type_id": 3,
     *                 "plan_id": 1,
     *                 "title": "Jab-Jab-Cross",
     *                 "video_title": "Susan Kocab's Jab-Jab-Cross",
     *                 "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523734899.jpg",
     *                 "duration": "00:49",
     *                 "trainer": {
     *                     "id": 1,
     *                     "type": 1,
     *                     "first_name": "Susan",
     *                     "last_name": "Kocab"
     *                 },
     *                 "rating": "5.0",
     *                 "filter": 1
     *             },
     *          ],
     *          "combinations": [
     *              {
     *                  "type_id": 3,
     *                  "plan_id": 1,
     *                  "title": "Jab-Jab-Cross",
     *                  "video_title": "Susan Kocab's Jab-Jab-Cross",
     *                  "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523734899.jpg",
     *                  "duration": "00:49",
     *                  "trainer": {
     *                      "id": 1,
     *                      "type": 1,
     *                      "first_name": "Susan",
     *                      "last_name": "Kocab"
     *                  },
     *                  "rating": "5.0",
     *                  "filter": 1
     *              },
     *              {
     *                  "type_id": 3,
     *                  "plan_id": 20,
     *                  "title": "6-SR-2-SR-2",
     *                  "video_title": "Susan Kocab's Intermediate 6-SR-2-SR-2",
     *                  "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523817805.jpg",
     *                  "duration": "00:40",
     *                  "trainer": {
     *                      "id": 1,
     *                      "type": 1,
     *                      "first_name": "Susan",
     *                      "last_name": "Kocab"
     *                  },
     *                  "rating": "5.0",
     *                  "filter": 2
     *              },
     *          ],
     *          "sets": [
     *              {
     *                   "type_id": 4,
     *                   "plan_id": 2,
     *                   "title": "HEAD MOVEMENT",
     *                   "video_title": null,
     *                   "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523656641.png",
     *                   "duration": "",
     *                   "trainer": {
     *                       "id": 1,
     *                       "type": 1,
     *                       "first_name": "Susan",
     *                       "last_name": "Kocab"
     *                   },
     *                   "rating": "5.0",
     *                   "filter": 1,
     *                   "combos_count": 10
     *               },
     *               {
     *                   "type_id": 4,
     *                   "plan_id": 4,
     *                   "title": "Jab-Cross Flow",
     *                   "video_title": null,
     *                   "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523657053.png",
     *                   "duration": "",
     *                   "trainer": {
     *                       "id": 1,
     *                       "type": 1,
     *                       "first_name": "Susan",
     *                       "last_name": "Kocab"
     *                   },
     *                   "rating": "5.0",
     *                   "filter": 1,
     *                   "combos_count": 13
     *               },
     *          ],
     *          "workouts": [
     *              {
     *                  "type_id": 5,
     *                  "plan_id": 2,
     *                  "title": "Workout #2",
     *                  "video_title": "Workout 2",
     *                  "thumbnail": null,
     *                  "duration": null,
     *                  "trainer": {
     *                      "id": 1,
     *                      "type": 1,
     *                      "first_name": "Susan",
     *                      "last_name": "Kocab"
     *                  },
     *                  "rating": "5.0",
     *                  "filter": 2,
     *                  "rounds_count": 2
     *              }
     *          ],
     *          "essentials": [
     *              {
     *                  "type_id": null,
     *                  "plan_id": 27,
     *                  "title": "The Right Cross",
     *                  "video_title": "The Right Cross",
     *                  "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523667458.png",
     *                  "duration": "00:54",
     *                  "trainer": {
     *                      "id": 2,
     *                      "type": 1,
     *                      "first_name": "Pete",
     *                      "last_name": "V"
     *                  },
     *                  "rating": "5.0",
     *                  "filter": 1
     *              }
     *          ]
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

            $_trainer = \App\Trainers::select('id')->where(function ($q) use ($searchQuery) {
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
    	$featuredVideos = \App\Videos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))->where('is_featured', 1)->orderBy('order')->limit(5)->get();

        foreach ($featuredVideos as $video) {
            $data['featured'][] = $this->getPlanData($video);
        }

        // Combos
    	$_comboVideos = \App\Videos::select('type_id', 'videos.plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'), \DB::raw('(r.sum_of_ratings / r.total_ratings) AS rating'))
            ->leftJoin(\DB::raw("(SELECT plan_id, SUM(rating) AS 'sum_of_ratings', COUNT(rating) AS 'total_ratings' FROM ratings GROUP BY plan_id) r"), function($join) {
                $join->on('videos.plan_id', '=', 'r.plan_id');
            })->where('type_id', \App\Types::COMBO)
            ->orderBy('rating', 'desc')->orderBy('views', 'desc')->limit(5);

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
    	$_comboSetVideos = \App\Videos::select('type_id', 'videos.plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'), \DB::raw('(r.sum_of_ratings / r.total_ratings) AS rating'))
            ->leftJoin(\DB::raw("(SELECT plan_id, SUM(rating) AS 'sum_of_ratings', COUNT(rating) AS 'total_ratings' FROM ratings GROUP BY plan_id) r"), function($join) {
                $join->on('videos.plan_id', '=', 'r.plan_id');
            })
            ->where('type_id', \App\Types::COMBO_SET)
            ->orderBy('rating', 'desc')->orderBy('views', 'desc')->limit(5);
    	
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
    	$_workoutVideos = \App\Videos::select('type_id', 'videos.plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'), \DB::raw('(r.sum_of_ratings / r.total_ratings) AS rating'))
            ->leftJoin(\DB::raw("(SELECT plan_id, SUM(rating) AS 'sum_of_ratings', COUNT(rating) AS 'total_ratings' FROM ratings GROUP BY plan_id) r"), function($join) {
                $join->on('videos.plan_id', '=', 'r.plan_id');
            })
            ->where('type_id', \App\Types::WORKOUT)
            ->orderBy('rating', 'desc')->orderBy('views', 'desc')->limit(5);
    	
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
        $essentialVideos = \App\Videos::select('*', \DB::raw('id as plan_id'), \DB::raw('title as name'), \DB::raw('id as user_favorited'), \DB::raw('id as likes'))
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
     * @apiParam {Number} [trainer_id] ID of trainer
     * @apiParam {Number="1=Beginner", "2=Intermediate", "3=Advanced"} [filters] Filter ids comma seperated e.g. 1,2
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
     *             "rating": "5.0",
     *             "filter": 1
     *         },
     *         {
     *             "type_id": 3,
     *             "plan_id": 2,
     *             "title": "Jab-Cross",
     *             "video_title": "Susan Kocab's Jab-Cross",
     *             "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523734934.jpg",
     *             "duration": "00:30",
     *             "trainer": {
     *                 "id": 1,
     *                 "type": 1,
     *                 "first_name": "Susan",
     *                 "last_name": "Kocab"
     *             },
     *             "rating": "5.0",
     *             "filter": 1
     *         }
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
            return response()->json(['error' => 'true', 'message' => 'Invalid type id, should be 3, 4 or 5 respectively']);
        }

        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 10;

        $trainerId = (int) $request->get('trainer_id');
        $filterIds = explode( ',', trim($request->get('filters')) );
        $filterIds = array_filter($filterIds); // Clearning array if no any values

        $_planVideos = \App\Videos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))->where('type_id', $typeId)->offset($offset)->limit($limit);

        // To use for relationship
        if ($typeId == \App\Types::COMBO) {
            $planType = 'combo';
        } elseif ($typeId == \App\Types::COMBO_SET) {
            $planType = 'comboSet';
        } elseif ($typeId == \App\Types::WORKOUT) {
            $planType = 'workout';
        }

        // Filter by trainer
        if ($trainerId) {
            $_planVideos->whereHas($planType, function($query) use($trainerId) {
                $query->where('trainer_id', $trainerId);
            });
        }

        // Filter by skill-level
        if (count($filterIds)) {
            $_planVideos->whereHas($planType, function($query) use($filterIds) {
                $query->whereHas('tag', function($q) use($filterIds) {
                    $q->whereIn('filter_id', $filterIds);
                });
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

        // $planVideo = \App\Videos::select('type_id', 'plan_id', 'title', 'thumbnail', 'duration', \DB::raw('id as likes'))->where('type_id', $typeId)->where('plan_id', $planId)->first();

        switch ($typeId) {
            case \App\Types::COMBO:
                $plan = \App\Combos::get($planId);
                break;
            case \App\Types::COMBO_SET:
                $plan = \App\ComboSets::get($planId);
                break;
            case \App\Types::WORKOUT:
                $plan = \App\Workouts::get($planId);
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

        $ratingExists = \App\Ratings::where('user_id', \Auth::id())->where('type_id', $typeId)->where('plan_id', $planId)->exists();

        if (!$ratingExists) {
            \App\Ratings::create([
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
     * @apiParam {Number} [trainer_id] ID of trainer
     * @apiParam {Number="1=Beginner", "2=Intermediate", "3=Advanced"} [filters] Filter ids comma seperated e.g. 1,2
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "trainer_id": 5,
     *      "filter_id": 1,2,
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
     *           {
     *             "type_id": null,
     *             "plan_id": 27,
     *             "title": "The Right Cross",
     *             "video_title": "The Right Cross",
     *             "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523667458.png",
     *             "duration": "00:54",
     *             "trainer": {
     *                 "id": 2,
     *                 "type": 1,
     *                 "first_name": "Pete",
     *                 "last_name": "V"
     *             },
     *             "rating": "5.0",
     *             "filter": 1
     *         },
     *         {
     *             "type_id": null,
     *             "plan_id": 28,
     *             "title": "Ryan Martin's One of BEST",
     *             "video_title": "Ryan Martin's One of BEST",
     *             "thumbnail": "http://example.com/videos/thumbnails/thumb_video_1523817764.jpg",
     *             "duration": "00:33",
     *             "trainer": {
     *                 "id": 1,
     *                 "type": 1,
     *                 "first_name": "Susan",
     *                 "last_name": "Kocab"
     *             },
     *             "rating": "5.0",
     *             "filter": 2
     *         }
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

        $trainerId = (int) $request->get('trainer_id');
        $filterIds = explode( ',', trim($request->get('filters')) );
        $filterIds = array_filter($filterIds); // Clearning array if no any values

        // Essentials
        $_essentialVideos = \App\Videos::select('*', \DB::raw('id as plan_id'), \DB::raw('title as name'), \DB::raw('id as user_favorited'), \DB::raw('id as likes'), \DB::raw('id as filter'))
            ->where(function($query) {
                $query->whereNull('type_id')->orWhere('type_id', 0);
            })->offset($offset)->limit($limit);

        // Filter by trainer
        if ($trainerId) {
            $_essentialVideos->where('trainer_id', $trainerId);
        }

        // Filter by skill-level
        if (count($filterIds)) {
            $_essentialVideos->whereHas('filters', function($query) use($filterIds) {
                $query->whereIn('tag_filter_id', $filterIds);
            });
        }

        $essentialVideos = $_essentialVideos->get();

        $data = [];
        
        foreach ($essentialVideos as $essentialVideo) {
            $data[] = $this->getPlanData($essentialVideo);
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
        try
        {

            $id = (int) $id;

            $essentialVideo = \App\Videos::select('*', \DB::raw('id as user_favorited'), \DB::raw('id as likes'))
                ->where(function($query) {
                    $query->whereNull('type_id')->orWhere('type_id', 0);
                })->where('id', $id)->first();

            if (!$essentialVideo) {
                return response()->json(['error' => 'true', 'message' => 'Invalid request or video not found']);
            }

            $_essentialVideo = $essentialVideo->toArray();
            $_essentialVideo['trainer'] = ['id' => $essentialVideo->trainer->id, 'type' => $essentialVideo->trainer->type, 'first_name' => $essentialVideo->trainer->first_name, 'last_name' => $essentialVideo->trainer->last_name];

            unset($_essentialVideo['trainer_id']);

            $data = ['type_id' => 0, 'data' => json_encode($_essentialVideo)];

            return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
        }catch (\Exception $exception)
        {
            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);

        }
    }

    /**
     * Getting plan data for /guidance/home (optimized object)
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

    /**
     * Alter param
     */
    private function alterParam(&$param)
    {
        $param = "%$param%";
    }
}