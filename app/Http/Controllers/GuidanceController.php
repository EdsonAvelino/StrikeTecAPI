<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuidanceController extends Controller
{
	/**
     * @api {post} /guidance/home Guidance home screen
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
    		$data['featured'][] = $video;
    	}

    	$_combinations = \DB::table('__combos')->limit(5)->get();
    	
    	foreach ($_combinations as $combo) {
    		$combo->key_set = '1-2-SR-2';
    		$combo->video = $_video;
    		$combo->user_voted = true;
    		$combo->rate = mt_rand(1, 4) / 10 + rand(1, 4);
    		$combo->filters = [1, 3];

    		$data['combinations'][] = $combo;
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
}