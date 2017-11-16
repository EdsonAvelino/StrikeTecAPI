<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Goals;

class GoalController extends Controller
{

    /**
     * @api {post} /goal/add Add goal of user
     * @apiGroup Goals
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Date} start_date The timestamp of start date since 1970.1.1(unit is seccond)
     * @apiParam {Date} end_date   The timestamp of end date since 1970.1.1 (unit is seccond)
     * @apiParam {Number} activity_id Activity id e.g. 1 = Boxing, 2 = Kickboxing
     * @apiParam {Number} activity_type_id Activity Type id  Punches = 1, Workouts = 2 (type doesn’t depends on activity type)
     * @apiParam {Number} target target of activity
     * @apiParamExample {json} Input
     *    {
     *       "activity_id":1,
     *       "activity_type_id":2,
     *       "target":50,
     *       "start_date":1505088000,
     *       "end_date":1505088000
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} sessions List of sessions betweeen given date range
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Your goal has been added."
     *       "data":{
     *          "id":3
     *          }
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
        $startDate = ($request->start_date) ? $request->start_date : null;
        $endDate = ($request->end_date) ? $request->end_date : null;
        $goal_id = Goals::create([
                    'user_id' => $user_id,
                    'activity_id' => $request->get('activity_id'),
                    'activity_type_id' => $request->get('activity_type_id'),
                    'target' => $request->get('target'),
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ])->id;
        return response()->json(['error' => 'false', 'message' => 'Your goal has been added.', 'data' => ['id' => $goal_id]]);
    }

    /**
     * @api {post} /goal/edit edit goal of user
     * @apiGroup Goals
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} goal_id goal id to be edited
     * @apiParam {Number} [activity_id] Activity id e.g. 1 = Boxing, 2 = Kickboxing
     * @apiParam {Number} [activity_type_id] Activity Type id  Punches = 1, Workouts = 2 (type doesn’t depends on activity type)
     * @apiParam {Number} [target] target of activity
     * @apiParam {Date} [start_date] The timestamp of start date since 1970.1.1(unit is seccond)
     * @apiParam {Date} [end_date]  The timestamp of end date since 1970.1.1 (unit is seccond)
     * @apiParamExample {json} Input
     *    {
     *       "goal_id":1,
     *       "activity_id":1,
     *       "activity_type_id":2,
     *       "target":50,
     *       "start_date":1505088000,
     *       "end_date":1505088000
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} sessions List of sessions betweeen given date range
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Your goal has been updated."
     *       "data":{
     *          {
     *             "id": 12,
     *             "activity_id": 1,
     *             "activity_type_id": 2,
     *             "target": "50",
     *             "start_date": "1505088000",
     *             "end_date": "1505088000",
     *             "followed": 1,
     *             "done_count": 0
     *         }
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function updateGoal(Request $request)
    {
        $user_id = \Auth::user()->id;
        $goal_id = $request->goal_id;
        $goal = Goals::find($goal_id);
        $goal->start_date = ($request->get('start_date')) ? $request->get('start_date') : $goal->start_date;
        $goal->end_date = ($request->get('end_date')) ? $request->get('end_date') : $goal->end_date;
        $goal->activity_id = ($request->get('activity_id')) ? $request->get('activity_id') : $goal->activity_id;
        $goal->activity_type_id = ($request->get('activity_type_id')) ? $request->get('activity_type_id') : $goal->activity_type_id;
        $goal->target = ($request->get('target')) ? $request->get('target') : $goal->target;
        if ($goal->done_count > 0) {
            return response()->json(['error' => 'true', 'message' => 'You can not edit this goal.']);
        }
        Goals::where('id', $goal_id)->where('user_id', $user_id)
                ->update([
                    'activity_id' => $goal->activity_id,
                    'activity_type_id' => $goal->activity_type_id,
                    'target' => $goal->target,
                    'start_date' => $goal->start_date,
                    'end_date' => $goal->end_date]);
        $goals_data = Goals::select('id', 'activity_id', 'activity_type_id', 'target', 'start_date', 'end_date', 'followed', 'done_count')->where('id', $goal_id)->where('user_id', $user_id)->first();
        return response()->json(['error' => 'false', 'message' => 'Your goal has been updated.', 'data' => $goals_data]);
    }

    /**
     * @api {delete} /goal/{goal_id} delete goal of user
     * @apiGroup Goals
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} goal_id Goal id 
     * @apiParamExample {json} Input
     *    {
     *       "goal_id":1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} sessions List of sessions betweeen given date range
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Your goal has been deleted."
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function deleteGoal($id)
    {
        $user_id = \Auth::user()->id;
        try {
            Goals::where('user_id', $user_id)->findOrFail($id)->delete();
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'true', 'message' => 'This goal does not exist']);
        }
        return response()->json(['error' => 'false', 'message' => 'Your goal has been deleted']);
    }

    /**
     * @api {get} /goal list of goal
     * @apiGroup Goals
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} sessions List of sessions betweeen given date range
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": ""
     *      "data":{
     *          {
     *             "id": 12,
     *             "activity_id": 1,
     *             "activity_type_id": 2,
     *             "target": "50",
     *             "start_date": "1505088000",
     *             "end_date": "1505088000"
     *             "followed": 1,
     *         },
     *          {
     *             "id": 11,
     *             "activity_id": 1,
     *             "activity_type_id": 2,
     *             "target": "50",
     *             "start_date": "1505088000",
     *             "end_date": "1505088000",
     *             "followed": 0,
     *         }
     *       }
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getGoalList(Request $request)
    {
        $user_id = \Auth::user()->id;
        $goalList = Goals::select('id', 'activity_id', 'activity_type_id', 'target', 'start_date', 'end_date', 'followed')->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $goalList]);
    }

    /**
     * @api {post} /goal/follow follow/unfollow goal by user
     * @apiGroup Goals
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} goal_id Goal id 
     * @apiParam {boolean} follow if follow = true, follow goal and unless,unfollow it
     * @apiParamExample {json} Input
     *    {
     *       "goal_id": 1,
     *       "follow": true,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} sessions List of sessions betweeen given date range
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Your goal has been followed."
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function followGoal(Request $request)
    {
        $goal_id = $request->goal_id;
        $follow = filter_var($request->get('follow'), FILTER_VALIDATE_BOOLEAN);
        $user_id = \Auth::user()->id;
        Goals::where('id', $goal_id)
                ->where('user_id', $user_id)
                ->update(['followed' => $follow]);
        if ($follow == TRUE) {
            Goals::where('user_id', $user_id)->where('id', '!=', $goal_id)->update(['followed' => 0]);
            return response()->json(['error' => 'false', 'message' => 'Your goal has been followed.']);
        } else {
            return response()->json(['error' => 'false', 'message' => 'Your goal has been unfollowed.']);
        }
    }

}
