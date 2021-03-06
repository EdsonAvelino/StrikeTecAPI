<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Goals;
use App\Sessions;

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
     * @apiParam {Number} activity_type_id Activity Type id  Punches = 1, Workouts = 2 (type doesn't depends on activity type)
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
     * @apiSuccess {Object} data Id of added goal
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

        $userId = \Auth::user()->id;
        $startAt = ($request->start_date) ? $request->start_date : null;
        $endAt = ($request->end_date) ? $request->end_date : null;
        $startChk = date('Y-m-d', (int) $startAt);
        $endChk = date('Y-m-d', (int) $startAt);
        
        \Log::info('start date default ::'.$startAt);
        \Log::info('start date ::'.date('Y-m-d H:i:s', (int) $startAt));

        \Log::info('end date default ::'.$endAt);
        \Log::info('end date ::'.date('Y-m-d H:i:s', (int) $endAt));

        if ($startChk >= date('Y-m-d') && $endChk >= date('Y-m-d')) {
            if ($endChk < $startChk) {
                return response()->json(['error' => 'true', 'message' => 'Please choose end date greater than start date.']);
            }
            $goalId = Goals::create([
                        'user_id' => $userId,
                        'activity_id' => $request->get('activity_id'),
                        'activity_type_id' => $request->get('activity_type_id'),
                        'target' => $request->get('target'),
                        'start_at' => date('Y-m-d H:i:s', (int) $startAt),
                        'end_at' => date('Y-m-d H:i:s', (int) $endAt)
                    ])->id;
            return response()->json(['error' => 'false', 'message' => 'Your goal has been added.', 'data' => ['id' => $goalId]]);
        } else {
            return response()->json(['error' => 'true', 'message' => 'You can not add a goal from past date.']);
        }
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
     * @apiParam {Number} [activity_type_id] Activity Type id  Punches = 1, Workouts = 2 (type doesn't depends on activity type)
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
     * @apiSuccess {Object} data updated goal data
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
        $userId = \Auth::user()->id;
        $goalId = $request->goal_id;
        $goal = Goals::find($goalId);
        $startAt = ($request->start_date) ? $request->start_date : $goal->start_at;
        $startDate = date('Y-m-d H:i:s', (int) $startAt);
        $endAt = ($request->end_date) ? $request->end_date : $goal->end_at;
        $endDate = date('Y-m-d H:i:s', (int) $endAt);
        $goal->start_at = $startDate;
        $goal->end_at = $endDate;
        $goal->activity_id = ($request->get('activity_id')) ? $request->get('activity_id') : $goal->activity_id;
        $goal->activity_type_id = ($request->get('activity_type_id')) ? $request->get('activity_type_id') : $goal->activity_type_id;
        $goal->target = ($request->get('target')) ? $request->get('target') : $goal->target;
        if ($goal->done_count > 0) {
            return response()->json(['error' => 'true', 'message' => 'You can not edit this goal.']);
        }
        Goals::where('id', $goalId)->where('user_id', $userId)
                ->update([
                    'activity_id' => $goal->activity_id,
                    'activity_type_id' => $goal->activity_type_id,
                    'target' => $goal->target,
                    'start_at' => $goal->start_at,
                    'end_at' => $goal->end_at]);
        $goals_data = Goals::select('id', 'activity_id', 'activity_type_id', 'target', 'start_at as start_date', 'end_at as end_date', 'followed', 'done_count')
                        ->where('id', $goalId)->where('user_id', $userId)->first();
        $goals_data->start_date = strtotime($goals_data->start_date);
        $goals_data->end_date = strtotime($goals_data->end_date);
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
        $userId = \Auth::user()->id;
        try {
            Goals::where('user_id', $userId)->findOrFail($id)->delete();
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'true', 'message' => 'This goal does not exist']);
        }
        return response()->json(['error' => 'false', 'message' => 'Your goal has been deleted']);
    }

    /**
     * @api {get} /goals list of goals
     * @apiGroup Goals
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "start": 0,
     *      "limit": 10
     *    }
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
     *             "done_count": 10,     
     *             "shared": "true"
     *         },
     *          {
     *             "id": 11,
     *             "activity_id": 1,
     *             "activity_type_id": 2,
     *             "target": "50",
     *             "start_date": "1505088000",
     *             "end_date": "1505088000",
     *             "followed": 0,
     *             "done_count": 0,
     *             "shared": "false"
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
        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 20;
        $userId = \Auth::user()->id;

        $this->calculateGoal(); // Calculate data of followed 

        $goalList = Goals::select('id', 'activity_id', 'activity_type_id', 'target', \DB::raw('UNIX_TIMESTAMP(start_at) as start_date'), \DB::raw('UNIX_TIMESTAMP(end_at) as end_date'), 'followed', 'done_count', 'shared')
                        ->where('user_id', $userId)->orderBy('created_at', 'desc')
                        ->offset($offset)->limit($limit)->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $goalList]);
    }

    /**
     * @api {post} /goal/follow Follow/unfollow goal by user
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
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Your goal has been followed."
     *      "data":{ 
     *          "goal_id":5
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
    public function followGoal(Request $request)
    {
        $goalId = $request->goal_id;
        $follow = filter_var($request->get('follow'), FILTER_VALIDATE_BOOLEAN);
        $userId = \Auth::user()->id;
        $goal = Goals::where('id', $goalId)->where('user_id', $userId)->get()->first();
        $endDate = $goal->end_at;
        $today = date("Y-m-d");

        Goals::where('id', $goalId)
                ->where('user_id', $userId)
                ->update(['followed' => $follow, 'followed_at' => date("Y-m-d H:i:s")]);

        if ($follow == TRUE) {
            if ($today >= $endDate) {
                return response()->json(['error' => 'true', 'message' => 'You can not follow this goal,it has been expired.']);
            }

            Goals::where('user_id', $userId)->where('id', '!=', $goalId)->where('followed', 1)->update([ 'followed' => 0]);

            return response()->json(['error' => 'false', 'message' => 'Your goal has been followed.', 'data' => ['goal_id' => $goalId]]);
        } else {
            return response()->json(['error' => 'false', 'message' => 'Your goal has been unfollowed.', 'data' => ['goal_id' => $goalId]]);
        }
    }

    /**
     * @api {get} /goal/info Get goal information
     * @apiGroup Goals
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} goal_id Goal Id
     * @apiParamExample {json} Input
     *    {
     *      "goal_id": 16
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data goal information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": ""
     *       "data": {
     *              "id": 16,
     *              "user_id": 7,
     *              "activity_id": 1,
     *              "activity_type_id": 2,
     *              "target": "50",
     *              "start_date": "1505088000",
     *              "end_date": "1505088000",
     *              "followed": 0,
     *              "followed_date": "1505088000",
     *              "done_count": 0,
     *              "avg_time": 0,
     *              "avg_speed": 0,
     *              "avg_power": 0,
     *              "achieve_type": 0
     *              "shared": "true",
     *              "achievements": [
     *           {
     *              "achievement_id": 5,
     *              "achievement_name": "Most Powerful Punch",
     *              "name": "Powerful Punch",
     *              "description": "Most Powerful Punch",
     *              "image": "http://54.233.233.189/storage/badges/Punch_Count_5000.png",
     *              "badge_value": 1,
     *              "awarded": true,
     *              "count": 1,
     *              "shared": false
     *          },
     *          {
     *              "achievement_id": 12,
     *              "achievement_name": "Iron First",
     *              "name": "Gold",
     *              "description": "User Participation",
     *               "image": "http://54.233.233.189/storage/badges/Punch_Count_5000.png",
     *              "badge_value": 1,
     *              "awarded": true,
     *              "count": 1,
     *              "shared": false
     *          }
     *         }
     *       }]
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function goalInfo(Request $request)
    {
        $goalId = (int) $request->get('goal_id');
        $userId = \Auth::user()->id;
        $goalList = Goals::select('id', 'activity_id', 'activity_type_id', 'target', \DB::raw('UNIX_TIMESTAMP(start_at) as start_date'), \DB::raw('UNIX_TIMESTAMP(end_at) as end_date'), 'followed', \DB::raw('UNIX_TIMESTAMP(followed_at) as followed_date'), 'done_count', 'avg_time', 'avg_speed', 'avg_power', 'achieve_type', 'shared')
                ->where('id', $goalId)
                ->where('user_id', $userId)
                ->first();

        $goalList['badge'] = \App\UserAchievements::getGoalAchievements($userId, $goalId);
        return response()->json(['error' => 'false', 'message' => '', 'data' => $goalList]);
    }

    /**
     * @api {get} /goal Get current followed goal
     * @apiGroup Goals
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data goal information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": ""
     *       "data": {
     *              "id": 16,
     *              "user_id": 7,
     *              "activity_id": 1,
     *              "activity_type_id": 2,
     *              "target": "50",
     *              "start_date": "1505088000",
     *              "end_date": "1505088000",
     *              "followed": 0,
     *              "followed_date": "1505088000",
     *              "done_count": 0,
     *              "avg_time": 0,
     *              "avg_speed": 0,
     *              "avg_power": 0,
     *              "achieve_type": 0,
     *              "shared": "false"
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function goal(Request $request)
    {
        $goalId = $this->calculateGoal(); // Calculate data of followed goal
        $goal = array();
        $message = 'No Goal is followed.';

        if ($goalId) {
            $goal = Goals::select('id', 'activity_id', 'activity_type_id', 'target', \DB::raw('UNIX_TIMESTAMP(start_at) as start_date'), \DB::raw('UNIX_TIMESTAMP(end_at) as end_date'), 'followed', \DB::raw('UNIX_TIMESTAMP(followed_at) as followed_date'), 'done_count', 'avg_time', 'avg_speed', 'avg_power', 'achieve_type', 'shared')
                            ->where('id', $goalId)->first();
            $message = '';
        }

        return response()->json(['error' => 'false', 'message' => $message, 'data' => (object) $goal]);
    }

    // Calculate followed goal data
    private function calculateGoal()
    {
        $userId = \Auth::user()->id;
        $goalList = Goals::select('id', 'avg_speed', 'avg_power', 'avg_time', 'done_count', 'activity_type_id', 'end_at')->where('user_id', $userId)->where('followed', 1)->first();

        if ($goalList) {
            $endDate = $goalList->end_at;
            $today = date("Y-m-d");

            if ($today >= $endDate) {
                $goalList->followed = 0;
                $goalList->followed_at = date("Y-m-d H:i:s");
                $goalList->save();
            } else {

                $goalSession = Goals::with('goalSessions')->where('id', $goalList->id)->first()->toArray();
                $sessionId = [];

                foreach ($goalSession['goal_sessions'] as $value) {
                    $sessionId[] = $value['session_id'];
                }

                $sessions = Sessions::where('user_id', \Auth::user()->id)
                                ->whereIn('id', $sessionId)
                                ->get()->toArray();
                $division = 0;
                $doneCount = 0;

                if (count($sessions)) {
                    foreach ($sessions as $session) {
                        $avgSpeedData[] = $session['avg_speed'] * $session['punches_count'];
                        $avgTimeData[] = $session['best_time'] * $session['punches_count'];
                        $avgForceData[] = $session['avg_force'] * $session['punches_count'];
                        $division += $session['punches_count'];
                        $doneCount++;
                    }

                    $avgSpeed = array_sum($avgSpeedData) / $division;
                    $avgForce = array_sum($avgForceData) / $division;
                    $avgTime = array_sum($avgTimeData) / $division;
                    $goalList->avg_speed = (int) $avgSpeed;
                    $goalList->avg_power = (int) $avgForce;
                    $goalList->avg_time = round($avgTime, 2);
                    if ($goalList->activity_type_id == 2) {
                        if ($session['type_id'] == 5) {
                            $goalList->done_count = $doneCount;
                        }
                    } else {
                        $goalList->done_count = $division;
                    }

                    $goalList->save();
                }

                return $goalList->id;
            }
        }
    }

}
