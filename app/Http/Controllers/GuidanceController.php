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
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Data object
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
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

        // Featured videos
    	$featuredItems = \App\GuidanceSlider::orderBy('order')->limit(5)->get();

        $featuredData = [];

        foreach ($featuredItems as $item) {
            $_featured = ['type_id' => $item->type_id, 'data' => null];
            
            switch ($item->type_id) {
                case \App\Types::COMBO:
                    $_featured['data'] = \App\NewCombos::get($item->plan_id);
                    break;
                case \App\Types::COMBO_SET:
                    $_featured['data'] = \App\NewComboSets::get($item->plan_id);
                    break;
                case \App\Types::WORKOUT:
                    $_featured['data'] = \App\NewWorkouts::get($item->plan_id);
                    break;
            }

            $_featured['data'] = json_encode($_featured['data']);
            $featuredData[] = $_featured;
        }
    	
    	$data['featured'][] = $featuredData;

        // Combos
    	$comboVideos = \App\NewVideos::select('plan_id', \DB::raw('id as likes'))->where('is_featured', 1)->where('type_id', \App\Types::COMBO)->orderBy('views', 'desc')->orderBy('likes', 'desc')->limit(5)->get();
    	
    	foreach ($comboVideos as $comboVideo) {
    		$combo = \App\NewCombos::get($comboVideo->plan_id);

    		$data['combinations'][] = ['type_id' => \App\Types::COMBO, 'data' => json_encode($combo)];
    	}

        // Combo-Sets
    	$comboSetVideos = \App\NewVideos::select('plan_id', \DB::raw('id as likes'))->where('is_featured', 1)->where('type_id', \App\Types::COMBO_SET)->orderBy('views', 'desc')->orderBy('likes', 'desc')->limit(5)->get();
    	
        foreach ($comboSetVideos as $comboSetVideo) {
            $comboSet = \App\NewComboSets::get($comboSetVideo->plan_id);

    		$data['sets'][] = ['type_id' => \App\Types::COMBO_SET, 'data' => json_encode($comboSet)];
    	}

        // Workouts
    	$workoutVideos = \App\NewVideos::select('plan_id', \DB::raw('id as likes'))->where('is_featured', 1)->where('type_id', \App\Types::WORKOUT)->orderBy('views', 'desc')->orderBy('likes', 'desc')->limit(5)->get();
    	foreach ($workoutVideos as $workoutVideo) {
    		$workout = \App\NewWorkouts::get($workoutVideo->plan_id);

    		$data['workouts'][] = ['type_id' => \App\Types::WORKOUT, 'data' => json_encode($workout)];
    	}

        // Essentials
        $essentialVideos = \App\NewVideos::select('id')->where(function($query) {
            $query->whereNull('type_id')->orWhere('type_id', 0);
        })->limit(5)->get();

        foreach ($essentialVideos as $essentialVideo) {
            $essential = \App\NewVideos::get($essentialVideo->id);

            $data['essentials'][] = json_encode($essential);
        }

	    return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /guidance/combos Guidance list of combos
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
     * @apiSuccess {Object} data Data object containing list of combos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *             "id": 1,
     *             "trainer_id": 1,
     *             "name": "Combo Nec Ligula",
     *             "description": "Proin dignissim ante ac leo tempor ",
     *             "detail": [ "1", "2", "SR", "2", "4" ],
     *             "video": {
     *                 "id": 5,
     *                 "type_id": 3,
     *                 "plan_id": 1,
     *                 "title": "Fighter Series 3",
     *                 "file": "http://videos.example.com/videos/video.mp4",
     *                 "thumbnail": "http://example.com/videos/thumbnails/thumb.png",
     *                 "duration": "00:00:37",
     *                 "views": 54,
     *                 "is_featured": true,
     *                 "user_favorited": false,
     *                 "likes": 1
     *             },
     *             "user_voted": false,
     *             "rating": 2.5,
     *             "filters": [
     *                 1,
     *                 2
     *             ]
     *         },
     *         {
     *             "id": 2,
     *             "trainer_id": 1,
     *             "name": "Combo Mauris Velit",
     *             "description": "Cras velit nibh, tempor quis sagittis in",
     *             "detail": [ "1", "2", "SL" ],
     *             "video": {
     *                 "id": 13,
     *                 "type_id": 3,
     *                 "plan_id": 2,
     *                 "title": null,
     *                 "file": "http://videos.example.com/videos/video.mp4",
     *                 "thumbnail": "http://example.com/videos/thumbnails/thumb.png",
     *                 "duration": null,
     *                 "views": 2,
     *                 "is_featured": true,
     *                 "user_favorited": false,
     *                 "likes": 0
     *             },
     *             "user_voted": false,
     *             "rating": 3,
     *             "filters": [1 , 2]
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
    public function getCombos(Request $request)
    {
        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 20;

        $combos = \App\NewCombos::select('id')->offset($offset)->limit($limit)->get();
        $data = [];

        foreach ($combos as $combo) {
            $data[] = \App\NewCombos::get($combo->id);
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /guidance/combo_sets Guidance list of combo-sets
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
     * @apiSuccess {Object} data Data object containing list of combos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *            "id": 1,
     *            "trainer_id": 1,
     *            "name": "Destroyer #1",
     *            "description": "Nullam neque nibh pellentesque eu dui sit amet",
     *            "detail": [ 1, 2, 3, 7, 9, 10 ],
     *            "video": {
     *                "id": 27,
     *                "type_id": 4,
     *                "plan_id": 1,
     *                "title": "Set series",
     *                "file": "http://localhost:8001/videos/",
     *                "thumbnail": "http://localhost:8001/videos/thumbnails/",
     *                "duration": null,
     *                "views": 38,
     *                "is_featured": true,
     *                "user_favorited": false,
     *                "likes": 0
     *            },
     *            "user_voted": false,
     *            "rating": 4,
     *            "filters": [
     *                1,
     *                2
     *            ]
     *        },
     *        {
     *            "id": 2,
     *            "trainer_id": 1,
     *            "name": "Fast Timing",
     *            "description": "Mauris enim lectus, posuere eget fringilla eu",
     *            "detail": [ 1, 4, 5 ],
     *            "video": {
     *                "id": 6,
     *                "type_id": 4,
     *                "plan_id": 2,
     *                "title": "Fighter Series 4: Louis Smolka at UFC Athlete Summit 2016",
     *                "file": "http://videos.example.com/videos/video.mp4",
     *                "thumbnail": "http://example.com/videos/thumbnails/thumb.png",
     *                "duration": "00:00:36",
     *                "views": 58,
     *                "is_featured": true,
     *                "user_favorited": false,
     *                "likes": 0
     *            },
     *            "user_voted": true,
     *            "rating": 2.7,
     *            "filters": []
     *        }
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
    public function getComboSets(Request $request)
    {
        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 20;

        $comboSets = \App\NewComboSets::select('id')->offset($offset)->limit($limit)->get();
        $data = [];

        foreach ($comboSets as $comboSet) {
            $data[] = \App\NewComboSets::get($comboSet->id);
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /guidance/workouts Guidance list of workouts
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
     * @apiSuccess {Object} data Data object containing list of combos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *             "id": 1,
     *             "trainer_id": 1,
     *             "name": "Workout-1",
     *             "description": "Aliquam eu iaculis nisl",
     *             "round_time": 5,
     *             "rest_time": 0,
     *             "prepare_time": 0,
     *             "warning_time": 5,
     *             "created_at": null,
     *             "updated_at": null,
     *             "detail": [
     *                 [
     *                     1,
     *                     2,
     *                     3
     *                 ],
     *                 [
     *                     1,
     *                     4,
     *                     5
     *                 ]
     *             ],
     *             "video": null,
     *             "user_voted": false,
     *             "rating": "0.0",
     *             "filters": []
     *         },
     *         {
     *             "id": 2,
     *             "trainer_id": 1,
     *             "name": "Workout-2",
     *             "description": "Sed finibus varius massa",
     *             "round_time": 4,
     *             "rest_time": 0,
     *             "prepare_time": 0,
     *             "warning_time": 4,
     *             "created_at": null,
     *             "updated_at": null,
     *             "detail": [
     *                 [
     *                     1,
     *                     3,
     *                     5
     *                 ],
     *                 [
     *                     2,
     *                     3,
     *                     4
     *                 ]
     *             ],
     *             "video": null,
     *             "user_voted": false,
     *             "rating": "0.0",
     *             "filters": [
     *                 1,
     *                 2
     *             ]
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
    public function getWorkouts(Request $request)
    {
        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 20;

        $workouts = \App\NewWorkouts::select('id')->offset($offset)->limit($limit)->get();

        $data = [];
        foreach ($workouts as $workout) {
            $data[] = \App\NewWorkouts::get($workout->id);
        }

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
            'type_id' => 'required|integer|in:1,2,3',
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
}