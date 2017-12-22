<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use App\FanActivity;

class FanActivityController extends Controller
{
    /**
     * @api {get} /fan/activities get fan Activity details information
     * @apiGroup event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event list information
     * @apiSuccessExample {json} Success
     *   {
     *      "error": "false",
     *      "message": "Activity list information",
     *      "data": [
     *          {
     *              "id": 1,
     *              "name": "Speed",
     *              "image_url": "",
     *              "status": 0,
     *              "updated_at": "2017-12-15 15:23:34",
     *              "created_at": "2017-12-15 15:23:34"
     *          },
     *          {
     *              "id": 2,
     *              "name": "Power",
     *              "image_url": "",
     *              "status": 0,
     *              "updated_at": "2017-12-15 15:23:34",
     *              "created_at": "2017-12-15 15:23:34"
     *          },
     *          {
     *              "id": 3,
     *              "name": "Endurance",
     *              "image_url": "",
     *              "status": 0,
     *              "updated_at": "2017-12-15 15:23:47",
     *              "created_at": "2017-12-15 15:23:47"
     *          }
     *      ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function getActivityList()
    {
        try {
            $activityList = FanActivity::get();
            return response()->json(['error' => 'false', 'message' => 'Activity list information', 'data' => $activityList]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }
    
}
