<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sessions;
use App\SessionRounds;
use App\SessionRoundPunches;
use App\Leaderboard;
use App\Battles;
use App\User;

use App\Helpers\Push;
use App\Helpers\PushTypes;

class TrainingController extends Controller
{
    /**
     * @api {get} /user/training/sessions Get list of sessions of user
     * @apiGroup Training
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Date} start_date Start Date in MM-DD-YYYY e.g. 09/11/2017
     * @apiParam {Date} end_date End Date in MM-DD-YYYY e.g. 09/15/2017
     * @apiParam {Number} [type_id] Optional Training type id e.g. 1 = Quick Start, 2 = Round, 3 = Combo, 4 = Set, 5 = Workout
     * @apiParamExample {json} Input
     *    {
     *      "start_date": "09/11/2017",
     *      "end_date": "09/15/2017",
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
     *          "end_time": null,
     *          "plan_id": -1,
     *          "avg_speed": "20.16",
     *          "avg_force": "348.03",
     *          "punches_count": 31,
     *          "max_speed": "34.00",
     *          "max_force": "549.00",
     *          "created_at": "2017-09-09 18:03:57",
     *          "updated_at": "2017-09-09 18:03:57",
     *          "round_ids" : [{ "id":1},{"id":2} ]}
     *      },
     *      {
     *          "id": 2,
     *          "user_id": 1,
     *          "type_id": 1,
     *          "start_time": "1504978767000",
     *          "end_time": null,
     *          "plan_id": -1,
     *          "avg_speed": "20.16",
     *          "avg_force": "348.03",
     *          "punches_count": 31,
     *          "max_speed": "34.00",
     *          "max_force": "549.00",
     *          "created_at": "2017-09-09 18:08:21",
     *          "updated_at": "2017-09-09 18:08:21"
     *          "round_ids" : [{ "id":3},{"id":4} ]}
     *      },
     *      {
     *          "id": 3,
     *          "user_id": 1,
     *          "type_id": 1,
     *          "start_time": "1505025567000",
     *          "end_time": null,
     *          "plan_id": -1,
     *          "avg_speed": "20.16",
     *          "avg_force": "348.03",
     *          "punches_count": 31,
     *          "max_speed": "34.00",
     *          "max_force": "549.00",
     *          "created_at": "2017-09-10 18:09:30",
     *          "updated_at": "2017-09-10 18:09:30"
     *          "round_ids" : [{ "id":5},{"id":6} ]}
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

        $startDate = ($startDate) ? date('Y-m-d', strtotime($startDate)) . ' 00:00:00' : null;
        $endDate = ($endDate) ? date('Y-m-d', strtotime($endDate)) . ' 23:59:59' : null;

        $_sessions = Sessions::where('user_id', $userId);

        if (!empty($startDate) && !empty($endDate)) {
            $_sessions->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($trainingTypeId) {
            $_sessions->where('type_id', $trainingTypeId);
        }

        $sessions = [];

        foreach ($result = $_sessions->get() as $_session) {
            $temp = $_session->toArray();

            $roundIDs = \DB::select( \DB::raw("SELECT id FROM session_rounds WHERE session_id = $_session->id") );

            $temp['round_ids'] = $roundIDs;
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
     *          "end_time": null,
     *          "plan_id": -1,
     *          "avg_speed": "20.16",
     *          "avg_force": "348.03",
     *          "punches_count": 31,
     *          "max_speed": "34.00",
     *          "max_force": "549.00",
     *          "created_at": "2017-09-09 18:03:57",
     *          "updated_at": "2017-09-09 18:03:57"
     *      }
     *      "rounds": [{
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
     *      {
     *          "id": 2,
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
     *      }],
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

        return response()->json([
                'error' => 'false',
                'message' => '',
                'session' => $session->toArray(),
                'rounds' => $rounds->toArray()
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
             "Content-Type": "application/json"
     *     }
     * @apiParam {json} data Json formatted sessions data
     * @apiParamExample {json} Input
     * {
     * "data": [
     *      { "type_id": 1, "battle_id": 0, "start_time": 1505745766000, "end_time": "", "plan_id":-1, "avg_speed": 21.87,  "avg_force" : 400.17, "punches_count" : 600, "max_force" : 34, "max_speed": 599, "best_time": 0.48 },
     *      { "type_id": 1, "battle_id": 0, "start_time": 1505792485000, "end_time": "", "plan_id":-1, "avg_speed": 20.55,  "avg_force" : 350.72, "punches_count" : 300, "max_force" : 35, "max_speed": 576, "best_time": 0.46 }
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
     *          {"start_time": 1505745766000},
     *          {"start_time": 1505745775000},
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

        try {
            foreach ($data as $session) {
                $_session = Sessions::create([
                        'user_id' => \Auth::user()->id,
                        'battle_id' => ($session['battle_id']) ?? null,
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

                $sessionRounds = SessionRounds::where('session_id', $_session->start_time)->update(['session_id' => $_session->id]);

                $sessions[] = ['start_time' => $_session->start_time];

                // Update battle details, if any
                if ($_session->battle_id) {
                    $battle = Battles::where('id', $_session->battle_id)->first();

                    if (\Auth::user()->id == $battle->user_id) {
                        $battle->user_finished = 1;
                        $battle->user_finished_at = date('Y-m-d H:i:s');
                        
                        $pushToUserId = $battle->opponent_user_id;
                        $pushOpponentUserId = $battle->user_id;
                    } else if (\Auth::user()->id == $battle->opponent_user_id) {
                        $battle->opponent_finished = 1;
                        $battle->opponent_finished_at = date('Y-m-d H:i:s');
                        
                        $pushToUserId = $battle->user_id;
                        $pushOpponentUserId = $battle->opponent_user_id;
                    }
                    
                    // TODO Push to opponent, about battle is finished by current user
                    $pushMessage = 'User has finished battle';
                    $pushOpponentUser = User::get($pushOpponentUserId);

                    Push::send($pushToUserId, PushTypes::BATTLE_FINISHED, $pushMessage, $pushOpponentUser);

                    $battle->update();
                }
            }

            // User's total sessions count
            $sessionsCount = Sessions::where('user_id', \Auth::user()->id)->count();
            $punchesCount = Sessions::select(\DB::raw('SUM(punches_count) as punches_count'))->where('user_id', \Auth::user()->id)->pluck('punches_count')->first();

            // Create / Update Leaderboard entry for this user
            $leaderboardStatus = Leaderboard::where('user_id', \Auth::user()->id)->first();

            // Set all old averate data to 0
            $oldAvgSpeed = $oldAvgForce = $oldPunchesCount = 0;

            if (!$leaderboardStatus) {
                $leaderboardStatus = Leaderboard::create([
                    'user_id' => \Auth::user()->id,
                    'sessions_count' => $sessionsCount,
                    'punches_count' => $punchesCount
                ]);
            } else {
                $oldAvgSpeed = $leaderboardStatus->avg_speed;
                $oldAvgForce = $leaderboardStatus->avg_force;
                $oldPunchesCount = $leaderboardStatus->punches_count;

                $leaderboardStatus->sessions_count = $sessionsCount;
                $leaderboardStatus->punches_count = $punchesCount;
                $leaderboardStatus->save();
            }

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
                    \DB::raw('MAX(max_speed) as max_speed'),
                    \DB::raw('MAX(max_force) as max_force')
                )
            ->whereRaw('session_id IN (SELECT id from sessions WHERE user_id = ?)', [\Auth::user()->id])->first();

            $leaderboardStatus->max_speed = $temp->max_speed;
            $leaderboardStatus->max_force = $temp->max_force;

            $totalTimeTrained = Sessions::select(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(start_time / 1000), FROM_UNIXTIME(end_time / 1000))) AS duration_in_sec'))->groupBy('user_id')->where('user_id', \Auth::user()->id)->pluck('duration_in_sec')->first();

            $leaderboardStatus->total_time_trained = $totalTimeTrained;

            $leaderboardStatus->save();

            return response()->json([
                'error' => 'false',
                'message' => 'Training sessions saved successfully',
                'data' => $sessions
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid request',
            ]);
        }
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
             "Content-Type": "application/json"
     *     }
     * @apiParam {json} data Json formatted rounds data
     * @apiParamExample {json} Input
     * {
     * "data": [
     *      { "session_start_time": 1505745766000, "start_time": 1505745866000, "end_time": 1505745866000, "avg_speed": 21.50, "avg_force": 364.25, "punches_count": 100, "max_speed": 32, "max_force": 579, "best_time": 0.50 },
     *      { "session_start_time": 1505792485000, "start_time": 1505792485080, "end_time": 1505792585000, "avg_speed": 22.57, "avg_force": 439.46, "punches_count": 120, "max_speed": 34, "max_force": 586, "best_time": 0.43}
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

                $_round = SessionRounds::create([
                        'session_id' => $round['session_start_time'],
                        'start_time' => $round['start_time'],
                        'end_time' => $round['end_time'],
                        'avg_speed' => $round['avg_speed'],
                        'avg_force' => $round['avg_force'],
                        'punches_count' => $round['punches_count'],
                        'max_speed' => $round['max_speed'],
                        'max_force' => $round['max_force'],
                        'best_time' => $round['best_time'],
                    ]);

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
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left" },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left" },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left" },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left" },
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
                
                // Store punch
                $_punch = SessionRoundPunches::create([
                        'session_round_id' => $sessionRound->id,
                        'punch_time' => $punch['punch_time'],
                        'punch_duration' => $punch['punch_duration'],
                        'force' => $punch['force'],
                        'speed' => $punch['speed'],
                        'punch_type' => strtoupper($punch['punch_type']),
                        'hand' => strtoupper($punch['hand']),
                    ]);

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
     * @apiParam {Date} start_date Start Date in MM-DD-YYYY e.g. 09/11/2017
     * @apiParam {Date} end_date End Date in MM-DD-YYYY e.g. 09/15/2017
     * @apiParamExample {json} Input
     *    {
     *      "type_id": 1,
     *      "start_date": "09/11/2017",
     *      "end_date": "09/15/2017",
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
                'message' => 'Invalid request',
            ]);
        }

        $startDate = ($startDate) ? date('Y-m-d 00:00:00', strtotime($startDate)) : null;
        $endDate = ($endDate) ? date('Y-m-d 23:59:59', strtotime($endDate)) : null;

        $_sessions = \DB::table('sessions')->select('id')->where('type_id', $trainingTypeId);

        if (!empty($startDate) && !empty($endDate)) {
            $_sessions->whereBetween('created_at', [$startDate, $endDate]);
        }

        $sessions = $_sessions->get();

        if (!$sessions) return null;        

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
}