<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Goals;

class GoalController extends Controller
{

    /**
     * @api {get} /goal/add Add goal of user
     * @apiGroup Goal
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Date} start_date Start Date in MM-DD-YYYY e.g. 09/11/2017
     * @apiParam {Date} end_date End Date in MM-DD-YYYY e.g. 09/15/2017
     * @apiParam {Number} [activity_id] Activity id e.g. 1 = Boxing, 2 = Kickboxing
     * @apiParam {Number} [activity_type_id] Activity Type id 
     * @apiParam {Number} [target] target of activity
     * @apiParamExample {json} Input
     *    {
     *       activity_id:1
     *       activity_type_id:2
     *       target:50
     *       start_date:09/11/2017
     *       end_date:09/11/2017
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} sessions List of sessions betweeen given date range
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Your goal has been added."
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function newGoal(Request $request)
    {

        $user_id = \Auth::user()->id;
        $startDate = ($request->start_date) ? date('Y-m-d', strtotime($request->start_date)) . ' 00:00:00' : null;
        $endDate = ($request->end_date) ? date('Y-m-d', strtotime($request->end_date)) . ' 23:59:59' : null;
        Goals::create([
            'user_id' => $user_id,
            'activity_id' => $request->get('activity_id'),
            'activity_type_id' => $request->get('activity_type_id'),
            'target' => $request->get('target'),
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        return response()->json(['error' => 'false', 'message' => 'Your goal has been added.']);
    }

}
