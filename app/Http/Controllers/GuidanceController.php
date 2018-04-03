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

    	$_featuredVideos = \DB::table('__videos')->where('is_featured', 1)->limit(5)->get();
    	
    	$_video = null;
    	foreach ($_featuredVideos as $video) {
    		$_video = $video;
    		$video->user_favorited = true;
    		$video->likes = rand(9, $video->views);
    		$data['featured'][] = json_encode($video);
    	}

    	$_combinations = \DB::table('__combos')->limit(5)->get();
    	
    	foreach ($_combinations as $combo) {
    		$combo->key_set = '1-2-SR-2';
    		$combo->video = $_video;
    		$combo->user_voted = true;
    		$combo->rate = mt_rand(1, 4) / 10 + rand(1, 4);
    		$combo->filters = [1, 3];

    		$data['combinations'][] = json_encode($combo);
    	}

    	$_sets = \DB::table('__combo_sets')->limit(5)->get();
    	foreach ($_sets as $set) {
    		$set->combos = [rand(1, 3), rand(4, 7), rand(8, 9)];
    		$set->video = $_video;
    		$set->user_voted = true;
    		$set->rate = mt_rand(1, 4) / 10 + rand(1, 4);
    		$set->filters = [1, 3];

    		$data['sets'][] = $set;
    	}

    	$_workouts = \DB::table('__workouts')->limit(5)->get();
    	foreach ($_workouts as $workout) {
    		$__combos[] = [rand(1, 3), rand(4, 7), rand(8, 9)];
    		$__combos[] = [rand(1, 3), rand(4, 7), rand(8, 9)];
    		$__combos[] = [rand(1, 3), rand(4, 7), rand(8, 9)];

    		$workout->combos = $__combos;
    		$workout->video = $_video;
    		$workout->user_voted = true;
    		$workout->rate = mt_rand(1, 4) / 10 + rand(1, 4);
    		$workout->filters = [1, 3];

    		$data['workouts'][] = $workout;
    		$__combos = null;
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
     *                 "file": "http://videos.example.com/video.mp4",
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

        $combos = \App\NewCombos::select('*', \DB::raw('id as key_set'))->offset($offset)->limit($limit)->get()->toArray();

        foreach ($combos as $i => $combo) {
            $combos[$i]['detail'] = explode('-', $combo['key_set']);
            unset($combos[$i]['key_set']);

            // Video
            $video = \App\NewVideos::where('type_id', \App\Types::COMBO)->where('plan_id', $combo['id'])->first();

            if ($video) {
                $video['user_favorited'] = (bool) \App\UserFavVideos::where('user_id', \Auth::id())->where('video_id', $video->id)->exists();
                $video['likes'] = (int) \App\UserFavVideos::where('video_id', $video->id)->count();
            }

            $combos[$i]['video'] = $video;
            
            // User rated combo
            $combos[$i]['user_voted'] = (bool) \App\NewRatings::where('user_id', \Auth::id())->where('type_id', \App\Types::COMBO)->where('plan_id', $combo['id'])->exists();
            
            // Combo rating
            $rating = \App\NewRatings::select(\DB::raw('SUM(rating) as sum_of_ratings'), \DB::raw('COUNT(rating) as total_ratings'))->where('type_id', \App\Types::COMBO)->where('plan_id', $combo['id'])->first();
            $combos[$i]['rating'] = ($rating->total_ratings > 0) ? $rating->sum_of_ratings / $rating->total_ratings : 0;

            // Skill levels
            $combos[$i]['filters'] = \App\NewComboTags::select('filter_id')->where('combo_id', $combo['id'])->get()->pluck('filter_id');
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $combos]);
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

    }

    /**
     * @api {post} /guidance/rate Rate combo/set/workout
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