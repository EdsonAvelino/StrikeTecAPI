<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

use App\Sessions;
use App\SessionRounds;
use App\SessionRoundPunches;
use App\Leaderboard;
use App\GameLeaderboard;
use App\Battles;
use App\Videos;
use App\UserAchievements;
use App\Achievements;
use App\AchievementTypes;
use App\GoalAchievements;
use App\GoalSession;
use App\Goals;

use App\Helpers\Push;
use App\Helpers\PushTypes;

class TrainingController extends Controller
{
    /**
     * @api {post} /user/training/data Store Training (Sensor) Data
     * @apiGroup Training
     * @apiDescription Used to store sensor data generated while traninig in csv format
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *       "Content-Type": "multipart/form-data"
     *     }
     * @apiParam {File} data_file Data file to store on server
     * @apiParamExample {json} Input
     *    {
     *      "data_file": "csv_file_to_upload.csv",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Stored",
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request or what error message is"
     *      }
     * @apiVersion 1.0.0
     */
    public function storeData(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'data_file' => 'required|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors->first('data_file')]);
        }

        $file = trim($request->file('data_file')->getClientOriginalName());
        
        // Getting date from timestamp in filename
        $exploded = explode('_', $file);
        $timestamp = (int) end($exploded);
        $dt = date('Y_m_d', ($timestamp/1000));

        $uploadDir = env('DATA_STORAGE_URL').\Auth::id().DIRECTORY_SEPARATOR.$dt;
        
        // Create dir if not created
        if (!is_dir(env('DATA_STORAGE_URL').\Auth::id())) {
            mkdir(env('DATA_STORAGE_URL').\Auth::id());
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir);
        }
        
        $file = str_replace([' ', '-'], '_', $file); // Replaces all spaces with underscore.
        $file = preg_replace('/[^A-Za-z0-9_.\-]/', '', $file); // Removing all special chars

        $request->file('data_file')->move($uploadDir, $file);

        return response()->json([
            'error' => 'false',
            'message' => 'Stored',
        ]);
    }

    /**
     * @api {get} /user/training/sessions Get list of sessions of user
     * @apiGroup Training
     * @apiDescription Used to get list of sessions of user, when any session is tied with
     * battle, that session will not be in response.
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Date} start_date The timestamp of start date since 1970.1.1(unit is seccond)
     * @apiParam {Date} end_date The timestamp of end date since 1970.1.1 (unit is seccond)
     * @apiParam {Number} [type_id] Optional Training type id e.g. 1 = Quick Start, 2 = Round, 3 = Combo, 4 = Set, 5 = Workout
     * @apiParamExample {json} Input
     *    {
     *      "start_date": "1505088000",
     *      "end_date": "1505088000",
     *      "type_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} sessions List of sessions betweeen given date range
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "sessions": [{
     *          "id": 1,
     *          "user_id": 1,
     *          "type_id": 1,
     *          "start_time": "1504960422890",
     *          "end_time": "1507203103523",
     *          "plan_id": -1,
     *          "avg_speed": 20.16,
     *          "avg_force": 348.03,
     *          "punches_count": 31,
     *          "max_speed": 34.00,
     *          "max_force": 549.00,
     *          "best_time": "0.50",
     *          "shared": "true",
     *          "created_at": "2017-09-09 18:03:57",
     *          "updated_at": "2017-09-09 18:03:57",
     *          "round_ids" : [{ "id":1}, {"id":2} ]}
     *      },
     *      {
     *          "id": 2,
     *          "user_id": 1,
     *          "type_id": 1,
     *          "start_time": "1504978767000",
     *          "end_time": "1507203088297",
     *          "plan_id": -1,
     *          "avg_speed": 20.16,
     *          "avg_force": 348.03,
     *          "punches_count": 31,
     *          "max_speed": 34.00,
     *          "max_force": 549.00,
     *          "best_time": "0.45",
     *          "shared": "false",
     *          "created_at": "2017-09-09 18:08:21",
     *          "updated_at": "2017-09-09 18:08:21"
     *          "round_ids" : [{'id':3}, {'id':4}]
     *      },
     *      {
     *          "id": 3,
     *          "user_id": 1,
     *          "type_id": 1,
     *          "start_time": "1505025567000",
     *          "end_time": "1507203103523",
     *          "plan_id": -1,
     *          "avg_speed": 20.16,
     *          "avg_force": 348.03,
     *          "punches_count": 31,
     *          "max_speed": 34.00,
     *          "max_force": 549.00,
     *          "best_time": "0.40",
     *          "shared": "true",
     *          "created_at": "2017-09-10 18:09:30",
     *          "updated_at": "2017-09-10 18:09:30"
     *          "round_ids" : [{"id":5}, {"id":6}]
     *      }
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getSessions(Request $request)
    {
        $userId = \Auth::user()->id;

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $trainingTypeId = (int) $request->get('type_id');

        // $startDate = ($startDate) ? date('Y-m-d 00:00:00', (int) $startDate) : null;
        // $endDate = ($endDate) ? date('Y-m-d 23:59:59', (int) $endDate) : null;

        $startDate = ($startDate) ? $startDate * 1000 : null;
        $endDate = ($endDate) ? ($endDate * 1000) - 1 : null;

        $_sessions = Sessions::select(['id', 'user_id', 'type_id', 'start_time', 'end_time', 'plan_id', 'avg_speed', 'avg_force', 'punches_count', 'max_speed', 'max_force', 'best_time', 'shared', 'created_at', 'updated_at'])->where('user_id', $userId);

        // Exclude battle & game sessions
        $_sessions->where(function ($query) {
            $query->whereNull('battle_id')->orWhere('battle_id', '0');
        })->where(function ($query) {
            $query->whereNull('game_id')->orWhere('game_id', '0');
        });

        // Exclude archived sessions
        $_sessions->where(function($query) {
            $query->whereNull('is_archived')->orWhere('is_archived', '0');
        });

        if (!empty($startDate) && !empty($endDate)) {
            // $_sessions->whereBetween('created_at', [$startDate, $endDate]);
            $_sessions->where('start_time', '>', $startDate);
            $_sessions->where('start_time', '<', $endDate);
        }

        if ($trainingTypeId) {
            $_sessions->where('type_id', $trainingTypeId);
        }

        $sessions = [];

        foreach ($result = $_sessions->get() as $_session) {
            switch ($_session->type_id) {
                case \App\Types::COMBO:
                    $plan = \App\Combos::get($_session->plan_id);
                    break;
                case \App\Types::COMBO_SET:
                    $plan = \App\ComboSets::get($_session->plan_id);
                    break;
                case \App\Types::WORKOUT:
                    $plan = \App\Workouts::getOptimized($_session->plan_id);
                    break;
                default:
                    $plan = null;
            }

            // Skipping sessions which has plan id but no plan details
            if ( in_array($_session->type_id, [\App\Types::COMBO, \App\Types::COMBO_SET, \App\Types::WORKOUT]) && !$plan) {
                continue;
            }

            $temp = $_session->toArray();

            $roundIDs = \DB::select(\DB::raw("SELECT id FROM session_rounds WHERE session_id = $_session->id"));

            $temp['round_ids'] = $roundIDs;
            
            if ($plan) {
                $planDetail = [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'detail' => $plan['detail']
                ];

                $temp['plan_detail'] = ['type_id' => (int) $_session->type_id, 'data' => json_encode($planDetail)];
            }

            $sessions[] = $temp;
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'sessions' => $sessions
        ]);
    }

    /**
     * @api {get} /user/training/sessions/<session_id> Get session and its rounds
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} session Sessions information
     * @apiSuccess {Object} rounds List of current session's rounds
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "session": {
     *          "id": 1,
     *          "user_id": 1,
     *          "type_id": 1,
     *          "start_time": "1504960422890",
     *          "end_time": "1504960423000",
     *          "plan_id": -1,
     *          "avg_speed": "20.16",
     *          "avg_force": "348.03",
     *          "punches_count": 31,
     *          "max_speed": "34.00",
     *          "max_force": "549.00",
     *          "best_time": "0.50",
     *          "shared": "true",
     *          "created_at": "2017-09-09 18:03:57",
     *          "updated_at": "2017-09-09 18:03:57"
     *      }
     *      "rounds": [{
     *          "id": 1,
     *          "session_id": 1,
     *          "start_time": "1504960422890",
     *          "end_time": "1504960423000",
     *          "avg_speed": 20.71,
     *          "avg_force": 358.64,
     *          "punches_count": 28,
     *          "max_speed": 34,
     *          "max_force": 549,
     *          "best_time": "0.39",
     *          "avg_time": "0.51",
     *          "created_at": "2017-09-09 18:06:33",
     *          "updated_at": "2017-09-09 18:06:33"
     *      },
     *      {
     *          "id": 2,
     *          "session_id": 1,
     *          "start_time": "1504960422890",
     *          "end_time": "15049604223000",
     *          "avg_speed": 20.71,
     *          "avg_force": 358.64,
     *          "punches_count": 28,
     *          "max_speed": 34,
     *          "max_force": 549,
     *          "best_time": "0.39",
     *          "avg_time": "0.51",
     *          "created_at": "2017-09-09 18:06:33",
     *          "updated_at": "2017-09-09 18:06:33"
     *      }]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getSession($sessionId)
    {
        $userId = \Auth::user()->id;

        $session = Sessions::where('id', $sessionId)->first();
        $rounds = SessionRounds::where('session_id', $sessionId)->get();

        if (empty($session)) {
            return response()->json([
                'error' => 'false',
                'message' => '',
                'session' => null,
                'rounds' => null
            ]);
        }

        $_session = $session->toArray();

        switch ($session->type_id) {
            case \App\Types::COMBO:
                $plan = \App\Combos::get($session->plan_id);
                break;
            case \App\Types::COMBO_SET:
                $plan = \App\ComboSets::get($session->plan_id);
                break;
            case \App\Types::WORKOUT:
                $plan = \App\Workouts::get($session->plan_id);
                break;
            default:
                $plan = null;
        }

        if ($plan) {
            $planDetail = [
                'id' => $plan['id'],
                'name' => $plan['name'],
                'description' => $plan['description'],
                'detail' => $plan['detail']
            ];

            $_session['plan_detail'] = ['type_id' => (int) $session->type_id, 'data' => json_encode($planDetail)];
        }
        

        return response()->json([
            'error' => 'false',
            'message' => '',
            'session' => $_session,
            'rounds' => $rounds->toArray()
        ]);
    }

    /**
     * @api {get} /user/training/sessions/for_comparison Get session of particular type to compare with last
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} session_id Desired Session ID
     * @apiParam {String} type_id Type ID
     * @apiParamExample {json} Input
     *    {
     *      "session_id": 25,
     *      "type_id": 1
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Two session, one which requested another latest of the same type
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": {
     *           {
     *               "id": 25,
     *               "user_id": 7,
     *               "battle_id": 15,
     *               "game_id": null,
     *               "type_id": 3,
     *               "start_time": 1522346134039,
     *               "end_time": 1522346137158,
     *               "plan_id": 3,
     *               "avg_speed": 15,
     *               "avg_force": 653,
     *               "punches_count": 3,
     *               "max_speed": 18,
     *               "max_force": 857,
     *               "best_time": "0.50",
     *               "shared": "false",
     *               "created_at": "2018-03-29T17:55:34.000000",
     *               "updated_at": "2018-03-29T18:00:32.000000",
     *               "plan_detail": {
     *                   "type_id": 3,
     *                   "data": "{\"id\":1,\"name\":\"Jab-Jab-Cross\",\"description\":\"BEGINNER SERIES\\r\\nJab-Jab-Cross (1-1-2)\",\"detail\":[\"1\",\"1\",\"2\"]}"
     *                 },
     *               "round_ids": [ {"id": 124} ]
     *           },
     *           {
     *               "id": 18,
     *               "user_id": 7,
     *               "battle_id": 14,
     *               "game_id": null,
     *               "type_id": 3,
     *               "start_time": 1522344517124,
     *               "end_time": 1522344520239,
     *               "plan_id": 3,
     *               "avg_speed": 17,
     *               "avg_force": 736,
     *               "punches_count": 3,
     *               "max_speed": 23,
     *               "max_force": 886,
     *               "best_time": "0.50",
     *               "shared": "false",
     *               "created_at": "2018-03-29T17:28:37.000000",
     *               "updated_at": "2018-03-29T17:27:40.000000",
     *               "plan_detail": {
     *                   "type_id": 3,
     *                   "data": "{\"id\":1,\"name\":\"Jab-Jab-Cross\",\"description\":\"BEGINNER SERIES\\r\\nJab-Jab-Cross (1-1-2)\",\"detail\":[\"1\",\"1\",\"2\"]}"
     *                 },
     *               "round_ids": [ {"id": 112 } ]
     *           }
     *      }
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getSessionForComparison(Request $request)
    {
        $sessionId = $request->get('session_id');
        $typeId = $request->get('type_id');

        $_sessions = Sessions::where(function($query) use ($sessionId) {
            $query->where('id', $sessionId)->orWhere('id', '<', $sessionId);
        })->where('type_id', $typeId)->where(function($query) {
            $query->whereNull('is_archived')->orWhere('is_archived', 0);
        })->where('user_id', \Auth::id())
        ->whereRaw('YEARWEEK(FROM_UNIXTIME(start_time / 1000), 1) = YEARWEEK(CURDATE(), 1)')
        ->orderBy('id', 'desc')->limit(2)->get();

        if (empty($_sessions)) {
            return response()->json([
                'error' => 'false',
                'message' => '',
                'data' => null,
            ]);
        }

        $sessions = [];

        foreach ($_sessions as $_session) {
            $session = $_session->toArray();

            switch ($_session->type_id) {
                case \App\Types::COMBO:
                    $plan = \App\Combos::get($_session->plan_id);
                    break;
                case \App\Types::COMBO_SET:
                    $plan = \App\ComboSets::get($_session->plan_id);
                    break;
                case \App\Types::WORKOUT:
                    $plan = \App\Workouts::get($_session->plan_id);
                    break;
                default:
                    $plan = null;
            }

            if ($plan) {
                $planDetail = [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'detail' => $plan['detail']
                ];

                $session['plan_detail'] = ['type_id' => (int) $_session->type_id, 'data' => json_encode($planDetail)];
            }

            $roundIDs = SessionRounds::select('id')->where('session_id', $_session->id)->get();
            $session['round_ids'] = $roundIDs;

            $sessions[] = $session;
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $sessions
        ]);
    }

    /**
     * @api {post} /user/training/sessions Upload sessions
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeader {String} content-type Content-Type set to "application/json"
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *       "Content-Type": "application/json"
     *     }
     * @apiParam {json} data Json formatted sessions data
     * @apiParamExample {json} Input
     * {
     * "data": [
     *      { "type_id": 1, "battle_id": 0, "game_id": 0, "start_time": 1505745766000, "end_time": "", "plan_id":-1, "avg_speed": 21.87,  "avg_force" : 400.17, "punches_count" : 600, "max_force" : 34, "max_speed": 599, "best_time": 0.48 },
     *      { "type_id": 1, "battle_id": 0, "game_id": 0, "start_time": 1505792485000, "end_time": "", "plan_id":-1, "avg_speed": 20.55,  "avg_force" : 350.72, "punches_count" : 300, "max_force" : 35, "max_speed": 576, "best_time": 0.46 }
     *  ]
     * }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains each sessions' start_time
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Training sessions saved successfully",
     *      "data": {[
     *          {
     *             "session_id": 639,
     *             "start_time": "1513591500000",
     *             "achievements": [
     *           {
     *              "achievement_id": 5,
     *              "achievement_name": "Most Powerful Punch",
     *              "name": "Powerful Punch",
     *              "description": "Most Powerful Punch",
     *              "image": "http://badges.example.com/Punch_Count_5000.png",
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
     *              "image": "http://badges.example.com/Punch_Count_5000.png",
     *              "badge_value": 1,
     *              "awarded": true,
     *              "count": 1,
     *              "shared": false
     *          }
     *         },
     *         {
     *             "session_id": 639,
     *             "start_time": "1513591500000",
     *             "achievements": []
     *         }
     *      ]}
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function storeSessions(Request $request)
    {
        $data = $request->get('data');
        $sessions = []; // Will be use for response

        $gameSession = false;

        foreach ($data as $session) {
            // Checking if session already exists
            $_session = Sessions::where('start_time', $session['start_time'])->first();

            if (!$_session) {
                $_session = Sessions::create([
                    'user_id' => \Auth::user()->id,
                    'battle_id' => ($session['battle_id']) ?? null,
                    'game_id' => ($session['game_id']) ?? null,
                    'type_id' => $session['type_id'],
                    'start_time' => $session['start_time'],
                    'end_time' => $session['end_time'],
                    'plan_id' => $session['plan_id'],
                    'avg_speed' => $session['avg_speed'],
                    'avg_force' => $session['avg_force'],
                    'punches_count' => $session['punches_count'],
                    'max_force' => $session['max_force'],
                    'max_speed' => $session['max_speed'],
                    'best_time' => $session['best_time']
                ]);

                SessionRounds::where('session_id', $_session->start_time)->update(['session_id' => $_session->id]);

                // Update battle details, if any
                if ($_session->battle_id) {
                    $this->updateBattle($_session->battleId);
                }
                // Game stuff
                elseif ($_session->game_id) {
                    $gameSession = true;
                    $this->updateGameLeaderboard($_session->game_id, $_session->id);
                }
                // Goal updates
                else {
                    $this->updateGoal($_session);
                }
            } else {
                SessionRounds::where('session_id', $_session->start_time)->update(['session_id' => $_session->id]);
            }

            // Process through achievements (badges) and assign 'em to user
            
            // skipping Achievements for now as they are not working properly
            // $achievements = $this->processAchievements($_session->id, $_session->battle_id);

            // Generating sessions' list for response
            $sessions[] = [
                'session_id' => $_session->id,
                'start_time' => $_session->start_time,
                'achievements' => []
            ];
        }

        // Sending response back if session is of game
        if ($gameSession) {
            return response()->json([
                'error' => 'false',
                'message' => 'Training sessions saved successfully',
                'data' => $sessions
            ]);
        }

        // User's total sessions count
        $sessionsCount = Sessions::where('user_id', \Auth::user()->id)->count();
        $punchesCount = Sessions::select(\DB::raw('SUM(punches_count) as punches_count'))->where('user_id', \Auth::user()->id)->pluck('punches_count')->first();

        // Create / Update Leaderboard entry for this user
        $leaderboardStatus = Leaderboard::where('user_id', \Auth::user()->id)->first();

        // Set all old averate data to 0
        $oldAvgSpeed = $oldAvgForce = $oldPunchesCount = 0;
         
        $oldAvgSpeed = $leaderboardStatus->avg_speed;
        $oldAvgForce = $leaderboardStatus->avg_force;
        $oldPunchesCount = $leaderboardStatus->punches_count;

        $leaderboardStatus->sessions_count = $sessionsCount;
        $leaderboardStatus->punches_count = $punchesCount;
        $leaderboardStatus->save();

        // Formula
        // (old avg speed x old total punches + session1's speed x session1's punch count + session2's speed x session2's punch count) / (old total punches + session1's punch count + session2's punchcount)

        $avgSpeedData[] = $oldAvgSpeed * $oldPunchesCount;
        $avgForceData[] = $oldAvgForce * $oldPunchesCount;

        $division = $oldPunchesCount;

        foreach ($data as $session) {
            $avgSpeedData[] = $session['avg_speed'] * $session['punches_count'];
            $avgForceData[] = $session['avg_force'] * $session['punches_count'];

            $division += $session['punches_count'];
        }

        $leaderboardStatus->avg_speed = array_sum($avgSpeedData) / $division;
        $leaderboardStatus->avg_force = array_sum($avgForceData) / $division;

        $temp = SessionRounds::select(
                                \DB::raw('MAX(max_speed) as max_speed'), \DB::raw('MAX(max_force) as max_force')
                        )
                        ->whereRaw('session_id IN (SELECT id from sessions WHERE user_id = ?)', [\Auth::user()->id])->first();

        $leaderboardStatus->max_speed = $temp->max_speed;
        $leaderboardStatus->max_force = $temp->max_force;

        $totalTimeTrained = Sessions::select(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(start_time / 1000), FROM_UNIXTIME(end_time / 1000))) AS duration_in_sec'))->groupBy('user_id')->where('user_id', \Auth::user()->id)->pluck('duration_in_sec')->first();

        $leaderboardStatus->total_time_trained = $totalTimeTrained;

        $leaderboardStatus->save();

        // Finally sending response back to request
        return response()->json([
            'error' => 'false',
            'message' => 'Training sessions saved successfully',
            'data' => $sessions
        ]);
    }

    /**
     * @api {patch} /user/training/sessions/<session_id>/archive Archive session
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Session has been archived",
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request or session not found"
     *      }
     * @apiVersion 1.0.0
     */
    public function archiveSession($sessionId)
    {
        $sessionId = (int) $sessionId;
        
        $session = Sessions::where('id', $sessionId)->where('user_id', \Auth::id())->first();

        if (!$sessionId || !$session) {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid request or session not found',
            ]);
        }

        $session->is_archived = true;
        $session->save();

        return response()->json([
            'error' => 'false',
            'message' => 'Session has been archived',
        ]);
    }

    /**
     * @api {get} /user/training/sessions/rounds/{round_id} Get rounds and its punches
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} round Round information
     * @apiSuccess {Object} punches List of round's punches
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "round": {
     *          "id": 1,
     *          "session_id": 1,
     *          "start_time": "1504960422890",
     *          "end_time": null,
     *          "avg_speed": 20.71,
     *          "avg_force": 358.64,
     *          "punches_count": 28,
     *          "max_speed": 34,
     *          "max_force": 549,
     *          "best_time": "0.39",
     *          "avg_time": "0.51",
     *          "created_at": "2017-09-09 18:06:33",
     *          "updated_at": "2017-09-09 18:06:33"
     *      },
     *      "punches": [{
     *          "id": 1,
     *          "round_id": 1,
     *          "punch_time": "1505089499658",
     *          "punch_duration": "0.60",
     *          "force": 270,
     *          "speed": 14,
     *          "punch_type": "H",
     *          "hand": "L",
     *          "distance": 35.49,
     *          "created_at": "2017-09-13 17:55:00",
     *          "updated_at": "2017-09-13 17:55:00"
     *      },
     *      {
     *          "id": 2,
     *          "round_id": 1,
     *          "punch_time": "1505089500659",
     *          "punch_duration": "0.40",
     *          "force": 217,
     *          "speed": 23,
     *          "punch_type": "H",
     *          "hand": "L",
     *          "distance": 49.20,
     *          "created_at": "2017-09-13 17:55:01",
     *          "updated_at": "2017-09-13 17:55:01"
     *      },
     *     {
     *          "id": 3,
     *          "round_id": 1,
     *          "punch_time": "1505089501660",
     *          "punch_duration": "0.50",
     *          "force": 549,
     *          "speed": 22,
     *          "punch_type": "J",
     *          "hand": "R",
     *          "distance": 55.57,
     *          "created_at": "2017-09-13 17:55:02",
     *          "updated_at": "2017-09-13 17:55:02"
     *      }]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getSessionsRound($roundId)
    {
        $round = SessionRounds::where('id', $roundId)->first();
        $punches = SessionRoundPunches::where('session_round_id', $roundId)->get();

        // If round not found, it will return null
        if (empty($round)) {
            return response()->json([
                'error' => 'false',
                'message' => '',
                'round' => null,
                'punches' => null
            ]);
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'round' => $round->toArray(),
            'punches' => $punches->toArray()
        ]);
    }

    /**
     * @api {post} /user/training/sessions/rounds Upload sessions' rounds
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeader {String} content-type Content-Type set to "application/json"
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     * "Content-Type": "application/json"
     *     }
     * @apiParam {json} data Json formatted rounds data
     * @apiParamExample {json} Input
     * {
     * "data": [
     *      { "session_start_time": 1505745766000, "start_time": 1505745866000, "end_time": 1505745866000, "pause_duration": 30000, "avg_speed": 21.50, "avg_force": 364.25, "punches_count": 100, "max_speed": 32, "max_force": 579, "best_time": 0.50 },
     *      { "session_start_time": 1505792485000, "start_time": 1505792485080, "end_time": 1505792585000, "pause_duration": 25000, "avg_speed": 22.57, "avg_force": 439.46, "punches_count": 120, "max_speed": 34, "max_force": 586, "best_time": 0.43}
     *  ]
     * }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains each rounds' session_start_time
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Sessions rounds saved successfully",
     *      "data": {[
     *          {"start_time": 1505745866000},
     *          {"start_time": 1505792485080},
     *      ]}
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function storeSessionsRounds(Request $request)
    {
        $data = $request->get('data');
        $rounds = [];

        try {
            foreach ($data as $round) {
                // $sessionId = Sessions::where('start_time', $round['session_start_time'])->first()->id;
                // Checking if round already exists
                $_round = SessionRounds::where('start_time', $round['start_time'])->where('session_id', $round['session_start_time'])->first();

                if (!$_round) {
                    $_round = SessionRounds::create([
                        'session_id' => $round['session_start_time'],
                        'start_time' => $round['start_time'],
                        'pause_duration' => $round['pause_duration'],
                        'end_time' => $round['end_time'],
                        'avg_speed' => $round['avg_speed'],
                        'avg_force' => $round['avg_force'],
                        'punches_count' => $round['punches_count'],
                        'max_speed' => $round['max_speed'],
                        'max_force' => $round['max_force'],
                        'best_time' => $round['best_time'],
                    ]);
                }

                $rounds[] = ['start_time' => $_round->start_time];
            }

            return response()->json([
                'error' => 'false',
                'message' => 'Sessions rounds saved successfully',
                'data' => $rounds
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {post} /user/training/sessions/rounds/punches Upload rounds' punches
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeader {String} content-type Content-Type set to "application/json"
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
      "Content-Type": "application/json"
     *     }
     * @apiParam {json} data Json formatted punches data
     * @apiParamExample {json} Input
     * {
     * "data": [
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left", "distance": 53.21, "is_correct": true },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left", "distance": 43.41, "is_correct": false },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left", "distance": 51.27, "is_correct": true },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left", "distance": 33.09, "is_correct": false },
     *  ]
     * }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains each punches' round_start_time
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Rounds punches saved successfully",
     *      "data": {[
     *          {"start_time": 1505745766000},
     *          {"start_time": 1505745766000},
     *          {"start_time": 1505745766000},
     *          {"start_time": 1505745766000},
     *      ]}
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function storeSessionsRoundsPunches(Request $request)
    {
        $data = $request->get('data');
        $punches = [];

        try {
            foreach ($data as $punch) {
                $sessionRound = SessionRounds::where('start_time', $punch['round_start_time'])->first();

                // Check if punches already exists
                $_punch = SessionRoundPunches::where('punch_time', $punch['punch_time'])->where('session_round_id', $sessionRound->id)->first();

                if (!$_punch) {
                    // To prevent errors on Prod
                    $isCorrect = null;

                    if (isset($punch['is_correct'])) {
                        $isCorrect = filter_var($punch['is_correct'], FILTER_VALIDATE_BOOLEAN);
                    }

                    $_punch = SessionRoundPunches::create([
                        'session_round_id' => $sessionRound->id,
                        'punch_time' => $punch['punch_time'],
                        'punch_duration' => $punch['punch_duration'],
                        'force' => $punch['force'],
                        'speed' => $punch['speed'],
                        'punch_type' => strtoupper($punch['punch_type']),
                        'hand' => strtoupper($punch['hand']),
                        'distance' => $punch['distance'],
                        'is_correct' => $isCorrect,
                    ]);
                }

                $punches[] = ['start_time' => $_punch->punch_time];
            }

            return response()->json([
                'error' => 'false',
                'message' => 'Rounds punches saved successfully',
                'data' => $punches
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {get} /user/training/sessions/rounds_by_training Get rounds by training-type
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *     }
     * @apiParam {Number} type_id Type ID e.g. 1 = Quick Start, 2 = Round, 3 = Combo, 4 = Set, 5 = Workout
     * @apiParam {Date} start_date The timestamp of start date since 1970.1.1(unit is seccond)
     * @apiParam {Date} end_date The timestamp of end date since 1970.1.1 (unit is seccond)
     * @apiParamExample {json} Input
     *    {
     *      "type_id": 1,
     *      "start_date": "1505088000",
     *      "end_date": "1505088000",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} rounds List of rounds by filtered by training-type
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Rounds punches saved successfully",
     *      "rounds": [
     *          {
     *          "id": 4,
     *          "session_id": 11,
     *          "start_time": "1505243114094",
     *          "end_time": "1505243115773",
     *          "avg_speed": 22,
     *          "avg_force": 258,
     *          "punches_count": 3,
     *          "max_speed": 24,
     *          "max_force": 269,
     *          "best_time": "0.47",
     *          "avg_time": "0.49",
     *          "created_at": "2017-09-12 19:07:28",
     *          "updated_at": "2017-09-13 17:55:18"
     *      },
     *          {
     *          "id": 5,
     *          "session_id": 10,
     *          "start_time": "1505243114090",
     *          "end_time": "1505243115780",
     *          "avg_speed": 29,
     *          "avg_force": 252,
     *          "punches_count": 9,
     *          "max_speed": 23,
     *          "max_force": 219,
     *          "best_time": "0.57",
     *          "avg_time": "0.47",
     *          "created_at": "2017-09-14 18:17:48",
     *          "updated_at": "2017-09-15 19:50:32"
     *      }
     *      ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getSessionsRoundsByTrainingType(Request $request)
    {
        // $sessions = \DB::table('sessions')->select('id')->where('type_id', $trainingTypeId)->get();

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $trainingTypeId = (int) $request->get('type_id');

        if (!$trainingTypeId) {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid type requested',
            ]);
        }

        $startDate = ($startDate) ? date('Y-m-d 00:00:00', $startDate) : null;
        $endDate = ($endDate) ? date('Y-m-d 23:59:59', $endDate) : null;

        $_sessions = \DB::table('sessions')->select('id')->where('type_id', $trainingTypeId);

        $_sessions->where(function($query) {
            $query->whereNull('battle_id')->orWhere('battle_id', '0');
        });

        if (!empty($startDate) && !empty($endDate)) {
            $_sessions->whereBetween('created_at', [$startDate, $endDate]);
        }

        $sessions = $_sessions->get();

        if (!$sessions)
            return null;

        $sessionIds = [];

        foreach ($sessions as $session)
            $sessionIds[] = $session->id;

        $rounds = SessionRounds::whereIn('session_id', $sessionIds)->get();

        return response()->json([
            'error' => 'false',
            'message' => '',
            'rounds' => $rounds
        ]);
    }

    // // Create goal session
    // public function storeGoalSession($goalId, $sessionId)
    // {
    //     GoalSession::create([
    //         'session_id' => $sessionId,
    //         'goal_id' => $goalId
    //     ]);
    // }

    /**
     * @api {get} /tips Get tips data
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *     }
     * @apiParam {Number} session_id Session ID 
     * @apiParamExample {json} Input
     *    {
     *      "session_id": 75
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data data of tips
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": {
     *          "current_speed": 20,
     *          "highest_speed": 25,
     *          "lowest_speed": 6,
     *          "current_force": 741,
     *          "highest_force": 804,
     *          "lowest_force": 1,
     *          "current_damage": 5689,
     *          "highest_damage": 464062,
     *          "lowest_damage": 217,
     *           "missing_punches": {
     *                      "S": 6,
     *                      "SR": 2,
     *                      "LH": 7,
     *                      "LU": 6,
     *                      "RU": 1,
     *                      "J": 4,
     *                      "SH": 1
     *                  },
     *          "videos": [
     *              {
     *                  "id": 1,
     *                  "category_id": 2,
     *                  "title": "Intro",
     *                  "file": "http://videos.example.com/video_1511358745.mp4",
     *                  "thumbnail": "http://videos.example.com/thumbnails/video_thumb_1511790678.png",
     *                  "view_counts": 211,
     *                  "duration": "00:00:06",
     *                  "author_name": "Striketec",
     *                  "price": null,
     *                  "thumb_width": 1338,
     *                  "thumb_height": 676
     *              },
     *              {
     *                  "id": 4,
     *                  "category_id": 2,
     *                  "title": "The Hook",
     *                  "file": "http://videos.example.com/video_1511357565.mp4",
     *                  "thumbnail": "http://videos.example.com/thumbnails/video_thumb_1511790074.jpg",
     *                  "view_counts": 19,
     *                  "duration": "00:00:55",
     *                  "author_name": "Striketec",
     *                  "price": null,
     *                  "thumb_width": 1327,
     *                  "thumb_height": 753
     *              },
     *              {
     *                  "id": 5,
     *                  "category_id": 3,
     *                  "title": "Right Handed Boxing Stance",
     *                  "file": "http://videos.example.com/video_1511357525.mp4",
     *                  "thumbnail": "http://videos.example.com/thumbnails/video_thumb_1511790106.jpg",
     *                  "view_counts": 26,
     *                  "duration": "00:00:27",
     *                  "author_name": "Striketec",
     *                  "price": null,
     *                  "thumb_width": 1341,
     *                  "thumb_height": 747
     *              },
     *          ]
     *      }
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function tips(Request $request)
    {
        $sessionId = (int) $request->get('session_id');
        $data = $this->getTipsData($sessionId);

        if ($data === false) {
            return response()->json([
                'error' => 'true',
                'message' => 'Session or round not found.'
            ]);
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => (object) $data
        ]);
    }

    // Get data calculated for tips
    private function getTipsData($sessionId)
    {
        $session = Sessions::select('id', 'plan_id', 'type_id', 'avg_speed', 'avg_force')
                        ->where(function ($query) use($sessionId) {
                            $query->where('id', $sessionId)->where('user_id', \Auth::user()->id);
                        })->first();

        if ($session) {
            $sessionType = $session->type_id;
            $sessionPlan = $session->plan_id;
            $sessionIds = $data = $force = [];
            if ($sessionType == 1 or $sessionType == 2) {
                $sessionIds = Sessions::select('id')->where('user_id', \Auth::user()->id)->where('type_id', $sessionType)->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->get()->toArray();

                $sessionData = Sessions::select(
                                \DB::raw('MAX(avg_speed) as highest_speed'), \DB::raw('MIN(avg_speed) as lowest_speed'), \DB::raw('MAX(avg_force) as highest_force'), \DB::raw('MIN(avg_force) as lowest_force')
                        )->where('user_id', \Auth::user()->id)->where('type_id', $sessionType)->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->first();
            } else {
                $sessionIds = Sessions::select('id')->where('user_id', \Auth::user()->id)
                                ->where(function ($query)use($sessionType, $sessionPlan) {
                                    $query->where('type_id', $sessionType)->where('plan_id', $sessionPlan);
                                })->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->get()->toArray();

                $sessionData = Sessions::select(
                                \DB::raw('MAX(avg_speed) as highest_speed'), \DB::raw('MIN(avg_speed) as lowest_speed'), \DB::raw('MAX(avg_force) as highest_force'), \DB::raw('MIN(avg_force) as lowest_force')
                        )->where('user_id', \Auth::user()->id)->where(function ($query)use($sessionType, $sessionPlan) {
                            $query->where('type_id', $sessionType)->where('plan_id', $sessionPlan);
                        })->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->first();
            }
            $data['current_speed'] = $session->avg_speed;
            $data['highest_speed'] = $sessionData->highest_speed;
            $data['lowest_speed'] = $sessionData->lowest_speed;
            $data['current_force'] = $session->avg_force;
            $data['highest_force'] = $sessionData->highest_force;
            $data['lowest_force'] = $sessionData->lowest_force;
            $sessionRounds = SessionRounds::with('punches')->select('id', 'session_id')->whereIn('session_id', $sessionIds)->get()->toArray();
            $roundForcesSum = [];
            $forceCount = 0;
            foreach ($sessionRounds as $sessionRound) {
                $punches = $sessionRound['punches'];
                if ($punches) {
                    $force[$forceCount] = [];
                    foreach ($punches as $forces) {
                        $force[$forceCount][] = $forces['force'];
                    }
                    $roundForcesSum[$sessionRound['session_id']][] = array_sum($force[$forceCount]);
                    $forceCount + 1;
                }
            }
            $sessionForce = [];
            foreach ($roundForcesSum as $sessionID => $roundForces) {
                $sessionForce[$sessionID] = array_sum($roundForces);
            }
            $data['current_damage'] = (int) $sessionForce[$sessionId];
            $data['highest_damage'] = max($sessionForce);
            $data['lowest_damage'] = min($sessionForce);
            $missingPunches = Sessions::getMissingPunches($session);
            $data['missing_punches'] = $missingPunches;

            $tag = [];
            $punchTypeTags = config('striketec.recommended_tags');
            if ($sessionType == 1 || $sessionType == 2) {
                if ($data['current_speed'] < 10) {
                    $tag[] = 1; //speed video
                }
                if ($data['current_force'] < 350) {
                    $tag[] = 2; //power video
                }
                if ($data['current_speed'] >= 25 && $data['current_force'] >= 450) {
                    $tag[] = 4; //recommended video
                }
            } else {
                foreach ($missingPunches as $key => $punchVideos) {
                    if ($sessionType == 3 || $sessionType == 4) {
                        if ($punchVideos > 1) {
                            $tag[] = $punchTypeTags[$key];
                        }
                    } else if ($sessionType == 5) {
                        if ($punchVideos > 5) {
                            $tag[] = $punchTypeTags[$key];
                        }
                    }
                }
            }
            if (count($tag) == 0) {
                $tag[] = 4; //recommended video
            }

            $_videos = Videos::select(['videos.*', 'thumbnail as thumb_width', 'thumbnail as thumb_height'])
                            ->join('recommend_videos', 'recommend_videos.video_id', '=', 'videos.id')
                            ->whereIn('recommend_tag_id', $tag)->distinct()->inRandomOrder()->limit(4)->get();


            $data['videos'] = $_videos;
            return $data;
        }

        return false;
    }

    // Process achievements, assigns new or updates existing one, based on achievement
    private function processAchievements($sessionId, $battleId = null)
    {
        $userId = \Auth::id();
        $goalId = Goals::getCurrentGoalId($userId);
        
        $achievements = Achievements::select('id')->orderBy('sequence')->get();
        
        foreach ($achievements as $achievement) {
            switch ($achievement->id) {
                // Badge BELT
                // TODO this may not needed, as there's one badge already "Champions"
                /*
                case Achievements::BELT:
                    $belts = Battles::getBeltCount(\Auth::id());

                    if ($belts > 0) {
                        $badge = AchievementTypes::select('id')->where('achievement_id', Achievements::BELT)->first();

                        if ($badge) {
                            $_userAchievement = UserAchievements::where('achievement_type_id', $badge->id)->where('user_id', $userId)->where('achievement_id', Achievements::BELT)->first();

                            if ($_userAchievement) {
                                if ($_userAchievement->metric_value < $belts) {
                                    $_userAchievement->metric_value = $belts;
                                    $_userAchievement->count = $belts;
                                    $_userAchievement->shared = false;
                                    $_userAchievement->session_id = $sessionId;
                                    $_userAchievement->awarded = true;
                                    $_userAchievement->save();
                                }
                            } else {
                                $_userAchievement = UserAchievements::create([
                                    'user_id' => $userId,
                                    'achievement_id' => $achievement->id,
                                    'achievement_type_id' => $achievementType->id,
                                    'metric_value' => $belts,
                                    'count' => $belts,
                                    'awarded' => true,
                                    'session_id' => $sessionId
                                ]);
                            }
                        }
                    }
                break;
                */
                
                // Badge Punches Count
                case Achievements::PUNCHES_COUNT:
                    $punchesCount = Sessions::getPunchesCountOfToday();

                    if ($punchesCount > 499) {
                        $badge = AchievementTypes::select('id', \DB::raw('MAX(config) as metric_value'))
                            ->where('config', '<=', $punchesCount)
                            ->where('achievement_id', Achievements::PUNCHES_COUNT)->first();

                        if ($badge) {
                            $_userAchievement = UserAchievements::where('achievement_type_id', $badge->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', Achievements::PUNCHES_COUNT)
                                    ->where('metric_value', $badge->metric_value)
                                    ->first();

                            if (!$_userAchievement) {
                                $_userAchievement = UserAchievements::create([
                                    'user_id' => $userId,
                                    'achievement_id' => Achievements::PUNCHES_COUNT,
                                    'achievement_type_id' => $badge->id,
                                    'metric_value' => $achievementType->metric_value,
                                    'awarded' => true,
                                    'goal_id' => $goalId,
                                    'session_id' => $sessionId
                                ]);
                            }
                        }
                    }
                break;

                // Badge Most Punches Per Minute
                case Achievements::MOST_PPM:
                    $mostPunches = 0;

                    if (!$battleId) {
                        $mostPPM = SessionRounds::getMostPunchesPerMinuteOfSession($sessionId);
                        
                        if ($mostPPM > 9) {
                            $badge = AchievementTypes::select('id', \DB::raw('MAX(config) as metric_value'))
                                ->where('config', '<=', $mostPPM)
                                ->where('achievement_id', Achievements::MOST_PPM)->first();

                            if ($badge) {
                                $_userAchievement = UserAchievements::where('achievement_type_id', $badge->id)
                                        ->where('user_id', $userId)
                                        ->where('achievement_id', Achievements::MOST_PPM)
                                        ->where('metric_value', $badge->metric_value)
                                        ->first();

                                if (!$_userAchievement) {
                                    $_userAchievement = UserAchievements::create([
                                        'user_id' => $userId,
                                        'achievement_id' => Achievements::MOST_PPM,
                                        'achievement_type_id' => $badge->id,
                                        'metric_value' => $badge->metric_value,
                                        'awarded' => true,
                                        'goal_id' => $goalId,
                                        'session_id' => $sessionId
                                    ]);
                                }
                            }
                        }
                    }
                break;

                // Badge Goal accomplishment
                case Achievements::ACCOMPLISH_GOAL:
                    $goalAccomplished = Goals::checkCurrentGoalAccomplished();

                    if ($goalAccomplished) {
                        $badge = AchievementTypes::select('id')->where('achievement_id', Achievements::ACCOMPLISH_GOAL)->first();

                        $_userAchievement = UserAchievements::where('achievement_type_id', $badge->id)
                                ->where('user_id', $userId)
                                ->where('achievement_id', Achievements::ACCOMPLISH_GOAL)
                                ->first();

                        // TODO need to check this
                        // Should be count++ not metric_value++ (not sure)
                        if ($_userAchievement) {
                            $goalMatrix = $_userAchievement->metric_value + 1;

                            $_userAchievement->metric_value = $goalMatrix;
                            $_userAchievement->session_id = $sessionId;
                            $_userAchievement->count = $goalMatrix;
                            $_userAchievement->shared = false;
                            $_userAchievement->awarded = true;
                            $_userAchievement->save();
                        } else {
                            $_userAchievement = UserAchievements::create([
                                'user_id' => $userId,
                                'achievement_id' => Achievements::ACCOMPLISH_GOAL,
                                'achievement_type_id' => $badge->id,
                                'metric_value' => 1,
                                'awarded' => true,
                                'count' => 1,
                                'session_id' => $sessionId
                            ]);
                        }
                    }
                break;

                // Badge Most Powerful Punch
                case Achievements::MOST_POWERFUL_PUNCH:
                    $maxForce = Sessions::select(\DB::raw('MAX(max_force)'))
                        ->where('user_id', \Auth::id())
                        ->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->where(function ($query) {
                            $query->whereNull('game_id')->orWhere('game_id', '0');
                        })->first();
                    
                    $badge = AchievementTypes::select('id')->where('achievement_id', Achievements::MOST_POWERFUL_PUNCH)->first();
                    
                    // User can earn first badge only when user has punched at least 100 LBF Power
                    if ($maxForce > 99) {
                        $_userAchievement = UserAchievements::where('achievement_type_id', $badge->id)
                                ->where('user_id', $userId)
                                ->where('achievement_id', Achievements::MOST_POWERFUL_PUNCH)
                                ->first();
                        
                        if ($_userAchievement) {
                            if ($_userAchievement->metric_value < $maxForce) {
                                $_userAchievement->metric_value = $maxForce;
                                $_userAchievement->session_id = $sessionId;
                                $_userAchievement->shared = false;
                                $_userAchievement->awarded = true;
                                $_userAchievement->save();
                            }
                        } else {
                            $_userAchievement = UserAchievements::create([
                                'user_id' => $userId,
                                'count' => 1,
                                'awarded' => true,
                                'achievement_id' => Achievements::MOST_POWERFUL_PUNCH,
                                'achievement_type_id' => $badge->id,
                                'metric_value' => $maxForce,
                                'session_id' => $sessionId
                            ]);
                        }
                    }

                break;
                
                // Badge Top Speed
                case Achievements::TOP_SPEED:
                    $maxSpeed = Sessions::select(\DB::raw('MAX(max_speed)'))
                        ->where('user_id', \Auth::id())
                        ->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->where(function ($query) {
                            $query->whereNull('game_id')->orWhere('game_id', '0');
                        })->first();

                    $badge = AchievementTypes::select('id')->where('achievement_id', Achievements::TOP_SPEED)->first();
                    
                    // Min speed 10 mph to earn this badge for the first time
                    if ($maxSpeed > 9) {
                        $_userAchievement = UserAchievements::where('achievement_type_id', $achievementType->id)
                                ->where('user_id', $userId)
                                ->where('goal_id', $goalId)
                                ->where('achievement_id', $achievement->id)
                                ->first();
                        
                        if ($_userAchievement) {
                            if ($_userAchievement->metric_value < $maxSpeed) {
                                $_userAchievement->metric_value = $maxSpeed;
                                $_userAchievement->session_id = $sessionId;
                                $_userAchievement->shared = false;
                                $_userAchievement->awarded = true;
                                $_userAchievement->save();
                            }
                        } else {
                            $_userAchievement = UserAchievements::create([
                                'user_id' => $userId,
                                'count' => 1,
                                'awarded' => true,
                                'achievement_id' => Achievements::TOP_SPEED,
                                'achievement_type_id' => $badge->id,
                                'metric_value' => $maxSpeed,
                                'session_id' => $sessionId
                            ]);
                        }
                    }
                break;
                
                // Badge Champion
                case Achievements::CHAMPION:
                break;

                // Badge Accuracy
                // TODO need proper calculation for this
                case Achievements::ACCURACY:
                    // TODO check this
                    $accuracy = Sessions::getAccuracy($perviousMonday);

                    if ($accuracy) {
                        $badge = AchievementTypes::select('id')
                                ->where('achievement_id', Achievements::ACCURACY)
                                ->where('min', '<', $accuracy)->where('max', '>', $accuracy)->first();

                        if ($badge) {
                            $_userAchievement = UserAchievements::where('achievement_id', Achievements::ACCURACY)
                                    ->where('user_id', $userId)
                                    ->where('achievement_type_id', $badge->id)
                                    ->first();

                            if ($accuracy > 0) {
                                if ($accuracyData) {
                                    if ($accuracyData->metric_value < $accuracy) {
                                        $accuracyData->metric_value = $accuracy;
                                        $accuracyData->achievement_type_id = $achievementType->id;
                                        $accuracyData->count = 1;
                                        $accuracyData->shared = false;
                                        $accuracyData->awarded = true;
                                        $accuracyData->save();
                                    }
                                } else {
                                    $_userAchievement = UserAchievements::create([
                                        'user_id' => $userId,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $achievementType->id,
                                        'metric_value' => $accuracy,
                                        'count' => 1,
                                        'awarded' => true,
                                    ]);
                                }
                            }
                        }
                    }
                break;

                // Badge Strong Man/Woman
                case Achievements::STRONG_MAN:
                    $config = $achievement->{\Auth::user()->gender};

                    // $strongMan = Sessions::getStrongMen($config, $userId, $perviousMonday);
                    
                    $currentWeekStart = strtotime("last monday midnight");
                    $currentWeekEnd = strtotime("next monday midnight", $currentWeekStart)-1;

                    $currentWeekSessionsCount = Sessions::where('user_id', \Auth::id())
                        ->where('start_time', '>', ($currentWeekStart * 1000))
                        ->where('start_time', '<', ($currentWeekEnd * 1000))
                        ->count();

                    // Minimum this badge needs 10 sessions
                    if ($currentWeekSessionsCount > 9) {
                        $avgForce = Sessions::select('avg_force')->where('user_id', \Auth::id())
                            ->where('start_time', '>', ($currentWeekStart * 1000))
                            ->where('start_time', '<', ($currentWeekEnd * 1000))
                            ->orderBy('avg_force', 'desc')->limit(1)->first()->pluck('avg_force');
                        
                        // Average power for male is over 500 lbs and for female is over 300 lbs
                        if ($avgForce > 499) {
                            $badge = AchievementTypes::select('id')
                                    ->where('achievement_id', $achievement->id)
                                    ->where('min', '<=', $currentWeekSessionsCount)
                                    ->where('max', '>=', $currentWeekSessionsCount)
                                    ->first();

                            if ($badge) {
                                $_userAchievement = UserAchievements::where('achievement_id', Achievements::STRONG_MAN)
                                        ->where('user_id', $userId)
                                        ->where('achievement_type_id', $badge->id)
                                        ->first();

                                if ($_userAchievement) {
                                    if ($_userAchievement->metric_value < $avgForce) {
                                        $_userAchievement->metric_value = $avgForce;
                                        $_userAchievement->count = 1;
                                        $_userAchievement->achievement_type_id = $badge->id;
                                        $_userAchievement->shared = false;
                                        $_userAchievement->awarded = true;
                                        $_userAchievement->save();
                                    }
                                } else {
                                    $_userAchievement = UserAchievements::create([
                                        'user_id' => $userId,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $badge->id,
                                        'metric_value' => $avgForce,
                                        'count' => 1,
                                        'awarded' => true,
                                    ]);
                                }
                            }
                        }
                    }
                break;

                // Badge Speed Deamon
                case Achievements::SPEED_DEAMON:
                    $config = $achievement->{\Auth::user()->gender};

                    $currentWeekStart = strtotime("last monday midnight");
                    $currentWeekEnd = strtotime("next monday midnight", $currentWeekStart)-1;

                    $currentWeekSessionsCount = Sessions::where('user_id', \Auth::id())
                        ->where('start_time', '>', ($currentWeekStart * 1000))
                        ->where('start_time', '<', ($currentWeekEnd * 1000))
                        ->count();

                    // Minimum this badge needs 10 sessions
                    if ($currentWeekSessionsCount > 9) {
                        $avgSpeed = Sessions::select('avg_speed')->where('user_id', \Auth::id())
                            ->where('start_time', '>', ($currentWeekStart * 1000))
                            ->where('start_time', '<', ($currentWeekEnd * 1000))
                            ->orderBy('avg_speed', 'desc')->limit(1)->first();
                        
                        // Average power for male is over 500 lbs and for female is over 300 lbs
                        if ($avgSpeed > 20) {
                            $badge = AchievementTypes::select('id')
                                    ->where('achievement_id', $achievement->id)
                                    ->where('min', '<=', $currentWeekSessionsCount)
                                    ->where('max', '>=', $currentWeekSessionsCount)
                                    ->first();

                            if ($badge) {
                                $_userAchievement = UserAchievements::where('achievement_id', Achievements::STRONG_MAN)
                                        ->where('user_id', $userId)
                                        ->where('achievement_type_id', $badge->id)
                                        ->first();

                                if ($_userAchievement) {
                                    if ($_userAchievement->metric_value < $avgSpeed) {
                                        $_userAchievement->metric_value = $avgSpeed;
                                        $_userAchievement->count = 1;
                                        $_userAchievement->achievement_type_id = $badge->id;
                                        $_userAchievement->shared = false;
                                        $_userAchievement->awarded = true;
                                        $_userAchievement->save();
                                    }
                                } else {
                                    $_userAchievement = UserAchievements::create([
                                        'user_id' => $userId,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $badge->id,
                                        'metric_value' => $avgSpeed,
                                        'count' => 1,
                                        'awarded' => true,
                                    ]);
                                }
                            }
                        }
                    }
                break;

                case Achievements::IRON_FIRST:
                    $ironFirst = Sessions::ironFirst($userId, $perviousMonday);
                    
                    if ($ironFirst) {
                        $achievementType = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('gender', $gender)
                                ->where('min', '<', $ironFirst)
                                ->where('max', '>', $ironFirst)
                                ->first();

                        if ($achievementType) {
                            $ironFirstData = UserAchievements::where('achievement_id', $achievement->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', $achievement->id)
                                    ->first();
                            if ($ironFirst > 0) {
                                if ($ironFirstData) {
                                    if ($ironFirstData->metric_value < $ironFirst) {
                                        $ironFirstData->metric_value = $ironFirst;
                                        $ironFirstData->count = 1;
                                        $ironFirstData->achievement_type_id = $achievementType->id;
                                        $ironFirstData->shared = false;
                                        $ironFirstData->awarded = true;
                                        $ironFirstData->save();
                                    }
                                } else {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $ironFirst,
                                                'count' => 1,
                                                'awarded' => true,
                                    ]);
                                }
                            } else {
                                if ($ironFirstData)
                                    UserAchievements::where('achievement_id', $achievement->id)->delete();
                            }
                        }
                    }
                break;
            }
        }

        // Finally, returns all achivements user got for this session
        // TODO improve this part
        return UserAchievements::getSessionAchievements($userId, $sessionId);
    }

    // Update battle
    private function updateBattle($battleId)
    {
        $battle = Battles::where('id', $battleId)->first();

        if (\Auth::id() == $battle->user_id) {
            $battle->user_finished = 1;
            $battle->user_finished_at = date('Y-m-d H:i:s');

            $pushToUserId = $battle->opponent_user_id;
            $pushOpponentUserId = $battle->user_id;
        } elseif (\Auth::user()->id == $battle->opponent_user_id) {
            $battle->opponent_finished = 1;
            $battle->opponent_finished_at = date('Y-m-d H:i:s');

            $pushToUserId = $battle->user_id;
            $pushOpponentUserId = $battle->opponent_user_id;
        }

        // Push to opponent, about battle is finished by current user
        $pushMessage = 'User has finished battle';

        // Set battle winner, according to battle-result
        Battles::updateWinner($battle->id);

        Push::send(PushTypes::BATTLE_FINISHED, $pushToUserId, $pushOpponentUserId, $pushMessage, ['battle_id' => $battle->id]);
        
        // Generates new notification for user
        $userThisBattleNotif = \App\UserNotifications::where('data_id', $battle->id)
                        ->where(function($query) {
                            $query->whereNull('is_read')->orWhere('is_read', 0);
                        })->where('user_id', \Auth::id())->first();

        if ($userThisBattleNotif) {
            $userThisBattleNotif->is_read = 1;
            $userThisBattleNotif->save();
        }

        \App\UserNotifications::generate(\App\UserNotifications::BATTLE_FINISHED, $pushToUserId, $pushOpponentUserId, $battle->id);

        $battle->update();
    }

    // Calculate & update game leaderboard
    private function updateGameLeaderboard($gameId, $sessionId)
    {
        if (!$gameId || !in_array($gameId, [1, 2, 3, 4]))
            return null;

        // ->whereRaw('YEARWEEK(FROM_UNIXTIME(start_time / 1000), 1) = YEARWEEK(CURDATE(), 1)')
        $currentSessionQuery = \DB::table('sessions')->select('id')->where('user_id', \Auth::id())->where('id', $sessionId);
        
        $currentSessionRoundsQuery = \DB::table('session_rounds')->select('id')->whereRaw("session_id IN (". \DB::raw("{$currentSessionQuery->toSql()}") .")")->mergeBindings($currentSessionQuery);

        $score = $distance = 0;

        switch ($gameId) {
            // game_id = 1, then you need min value of punch duration through punches of session, and store it leaderboard.
            case 1: // Reaction
                $score = \DB::table('session_round_punches')->select(\DB::raw('MIN(punch_duration) as min_punch_duration'))->where('is_correct', 1)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->pluck('min_punch_duration')->first();

                $raw = \DB::table('session_round_punches')->select('*')->where('punch_duration', $score)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->first();
                
                $distance = $raw->distance;
            break;

            // game_id = 2, then you can find max_speed from session table, and store it.
            case 2: // Speed
                $score = \DB::table('session_round_punches')->select(\DB::raw('MAX(speed) as max_speed'))->where('is_correct', 1)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->pluck('max_speed')->first();

                $raw = \DB::table('session_round_punches')->select('*')->where('speed', $score)->where('is_correct', 1)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->first();
                
                $distance = $raw->distance;
            break;

            // game_id = 3, then calculate ppm according to punch count of session, and time of session (endtime - start time)
            // ref: SessionRounds -> getMostPunchesPerMinute()
            case 3: // Endurance
                $result = $currentSessionRoundsQuery->select(
                    \DB::raw('SUM( (end_time - start_time) - pause_duration ) AS duration'),
                    \DB::raw('SUM(punches_count) as punches')
                )->first();

                $totalPPMOfRounds = $result->punches * 1000 * 60 / $result->duration;
                $roundsCountsOfSessions = $currentSessionQuery->count();

                // ppm of round1 + ppm of round2 + .... / round count of session
                $score = $totalPPMOfRounds / $roundsCountsOfSessions;

                $totalDistance = SessionRoundPunches::select(\DB::raw('SUM(distance) as total_distance'))->where('is_correct', 1)->whereRaw('session_round_id IN (SELECT id FROM session_rounds WHERE session_id = ?)', $sessionId)->pluck('total_distance')->first();
                $totalPunches = SessionRoundPunches::where('is_correct', 1)->whereRaw('session_round_id IN (SELECT id FROM session_rounds WHERE session_id = ?)', $sessionId)->count();

                $distance = $totalDistance / $totalPunches;
            break;

            // game_id == 4, then max_power will be stored.
            case 4: // Power
                $score = \DB::table('session_round_punches')->select(\DB::raw('MAX(`force`) as max_force'))->where('is_correct', 1)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->pluck('max_force')->first();

                $raw = \DB::table('session_round_punches')->select('*')->where('force', $score)->where('is_correct', 1)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->first();
                
                $distance = $raw->distance;
            break;
        }

        $score = (float) $score;
        $userGameLeaderboard = GameLeaderboard::where('user_id', \Auth::id())->where('game_id', $gameId)->first();

        if ($userGameLeaderboard) {
            // Reaction game, min value is better score
            $update = false; // Update or not
             
            if ($gameId == 1 && $userGameLeaderboard->score > $score) {
                $userGameLeaderboard->score = $score;
                $update = true;
            } elseif ($gameId != 1 && $userGameLeaderboard->score < $score) {
                $userGameLeaderboard->score = $score;
                $update = true;
            }

            if ($update) {
                $userGameLeaderboard->distance = $distance;
                $userGameLeaderboard->update();
            }
        } else {
            GameLeaderboard::create([
                'user_id' => \Auth::id(),
                'game_id' => $gameId,
                'score' => $score,
                'distance' => $distance,
            ]);
        }

        return true;
    }

    // Update Goal progress
    private function updateGoal($session)
    {
        $goal = Goals::where('user_id', \Auth::id())->where('followed', 1)
                ->where('start_at', '<=', date('Y-m-d H:i:s'))
                ->where('end_at', '>=', date('Y-m-d H:i:s'))
                ->first();

        if ($goal) {
            if ($goal->activity_type_id == 2) {
                if ($session->type_id == 5) {
                    GoalSession::create([
                        'session_id' => $session->id,
                        'goal_id' => $goal->id
                    ]);
                    
                    $goal->done_count = $goal->done_count + 1;
                    $goal->save();
                }
            } else {
                GoalSession::create([
                    'session_id' => $session->id,
                    'goal_id' => $goal->id
                ]);

                $goal->done_count = $_session->punches_count + $goal->done_count;
                $goal->save();
            }
        }
    }

    // Test for getting game score
    // public function test()
    // {
    //     // $gameId = 1;

    //     $currentSessionQuery = \DB::table('sessions')->select('id')->whereRaw('YEARWEEK(FROM_UNIXTIME(start_time / 1000), 1) = YEARWEEK(CURDATE(), 1)')->where('user_id', \Auth::id());
        
    //     $currentSessionRoundsQuery = \DB::table('session_rounds')->select('id')->whereRaw("session_id IN (". \DB::raw("{$currentSessionQuery->toSql()}") .")")->mergeBindings($currentSessionQuery);

    //     $score = \DB::table('session_round_punches')->select('id', \DB::raw('MIN(punch_duration) as min_punch_duration'))->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->pluck('min_punch_duration')->first();

    //     $rec = \DB::table('session_round_punches')->select('id')->where('punch_duration', $score)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->first();

    //     print_r($rec);

    //     // $score = $currentSessionQuery->select(\DB::raw("MAX(max_speed) as max_speed"))->pluck('max_speed')->first();

    //     // $score = $currentSessionQuery->select(\DB::raw("MAX(max_force) as max_force"))->pluck('max_force')->first();

    //     // first calculate ppm for round
    //     // like punch count of round / round duration * 60
    //     // and calculate avg ppm for session
        
    //     // $result = $currentSessionRoundsQuery->select(
    //     //     \DB::raw('SUM(end_time - start_time) AS duration'),
    //     //     \DB::raw('SUM(punches_count) as punches')
    //     // )->first();

    //     // $ppmOfRound = $result->punches * 1000 * 60 / $result->duration;

    //     // $roundCountsOfSession = $currentSessionQuery->count();
    // }
}
