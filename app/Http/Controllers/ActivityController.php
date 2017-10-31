<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Activities;
use App\ActivityTypes;

class ActivityController extends Controller
{

    /**
     * @api {get}/activities Get list of Activities
     * @apiGroup Goals
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} activities List of Activities
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data":[
     *                      {
     *                          "id": 1,
     *                          "activity_name": "Boxing"
     *                      },
     *                      {
     *                          "id": 2,
     *                          "activity_name": "kickboxing"
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
    public function getActivityList(Request $request)
    {
        $activityList = Activities::select('id', 'activity_name')->get();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $activityList]);
    }

    /**
     * @api {get} /activity/types/<activity_id> Get types of particular activity 
     * @apiGroup Goals
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} [activity_id] Activity id e.g. 1 = Boxing, 2 = Kickboxing
     * @apiParamExample {json} Input
     *    {
     *       activity_id:1
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} activity_types List of types of Activity
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *         {
     *             "id": 1,
     *             "activity_id": 1,
     *             "type_name": "no of punches"
     *         },
     *         {
     *             "id": 2,
     *             "activity_id": 1,
     *             "type_name": "no of workout"
     *         },
     *         {
     *             "id": 3,
     *             "activity_id": 1,
     *             "type_name": "duration"
     *         }
     *     ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getActivityTypeList(Request $request)
    {
        $activityId = (int) $request->get('activity_id');

        $activityTypes = ActivityTypes::select(['id', 'activity_id', 'type_name'])->where('activity_id', $activityId)->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $activityTypes]);
    }
}
