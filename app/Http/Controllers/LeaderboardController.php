<?php

namespace App\Http\Controllers;

use App\Leaderboard;

class LeaderboardController extends Controller
{
	/**
     * @api {get} /leaderboard Get leaderboard data
     * @apiGroup Leaderboard
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
     *      "videos": [
     *          {
     *              "id": 1,
     *              "title": null,
     *              "file": "http://example.com/videos/SampleVideo_1280x720_10mb.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/SampleVideo_1280x720_10mb.png",
     *              "view_counts": 250,
     *              "author_name": "Limer Waughts",
     *              "duration": "00:01:02",
     *              "user_favourited": true
     *          },
     *          {
     *              "id": 2,
     *              "title": null,
     *              "file": "http://example.com/videos/SampleVideo_1280x720_20mb.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/SampleVideo_1280x720_20mb.png",
     *              "view_counts": 170,
     *              "author_name": "Aeron Emeatt",
     *              "duration": "00:01:12",
     *              "user_favourited": true
     *          },
     *      ]
     *    }
     * @apiErrorExample {json} Login error (Invalid credentials)
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getList()
    {
    	// \DB::enableQueryLog();

    	\DB::statement(\DB::raw('SET @rank = 0'));

        $leadersList = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', 'country_id', 'state_id', 'city_id', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'));

            }])->select('*', \DB::raw('@rank:=@rank+1 AS rank'))->orderByRaw('(user_id = '. \Auth::user()->id .') desc')->orderBy('punches_count', 'desc')->get();

        // dd(\DB::getQueryLog());

        return response()->json(['error' => 'false', 'message' => '', 'data' => $leadersList->toArray()]);
    }
}
