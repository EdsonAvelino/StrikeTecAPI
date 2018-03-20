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
     *          "round_ids" : [{ "id":3}, {"id":4} ]}
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
     *          "round_ids" : [{ "id":5}, {"id":6} ]}
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

        $_sessions->where(function ($query) {
            $query->whereNull('battle_id')->orWhere('battle_id', '0');
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
            $temp = $_session->toArray();

            $roundIDs = \DB::select(\DB::raw("SELECT id FROM session_rounds WHERE session_id = $_session->id"));

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
     *       "Content-Type": "application/json"
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
        $sessions = []; //Will be use for response

        $gameSession = false;

        foreach ($data as $session) {
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

            $sessionRounds = SessionRounds::where('session_id', $_session->start_time)->update(['session_id' => $_session->id]);

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
            } elseif ($_session->game_id) {
                $gameSession = true;
                $this->updateGameLeaderboard($_session->game_id, $_session->id);
            } else {
                // Update goal progress
                $goal = Goals::where('user_id', \Auth::user()->id)->where('followed', 1)
                        ->where('start_at', '<=', date('Y-m-d H:i:s'))
                        ->where('end_at', '>=', date('Y-m-d H:i:s'))
                        ->first();

                if ($goal) {
                    if ($goal->activity_type_id == 2) {
                        if ($_session->type_id == 5) {
                            GoalSession::create([
                                'session_id' => $_session->id,
                                'goal_id' => $goal->id
                            ]);
                            
                            $goal->done_count = $goal->done_count + 1;
                            $goal->save();
                        }
                    } else {
                        GoalSession::create([
                            'session_id' => $_session->id,
                            'goal_id' => $goal->id
                        ]);

                        $goal->done_count = $_session->punches_count + $goal->done_count;
                        $goal->save();
                    }
                }
            }

            $achievements = $this->achievements($_session->id, $_session->battle_id);
            $sessions[] = ['session_id' => $_session->id, 'start_time' => $_session->start_time, 'achievements' => $achievements];
        }

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

        return response()->json([
            'error' => 'false',
            'message' => 'Training sessions saved successfully',
            'data' => $sessions
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
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left", "distance": 53.21 },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left", "distance": 43.41 },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left", "distance": 51.27 },
     *      { "round_start_time": 1505745766000, "punch_time": 1505745766000, "punch_duration": 0.5, "force" : 130, "speed" : 30, "punch_type" : "Jab", "hand" : "left", "distance": 33.09 },
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

                // Store punches
                $_punch = SessionRoundPunches::create([
                            'session_round_id' => $sessionRound->id,
                            'punch_time' => $punch['punch_time'],
                            'punch_duration' => $punch['punch_duration'],
                            'force' => $punch['force'],
                            'speed' => $punch['speed'],
                            'punch_type' => strtoupper($punch['punch_type']),
                            'hand' => strtoupper($punch['hand']),
                            'distance' => $punch['distance'],
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

    // Create goal session
    public function storeGoalSession($goalId, $sessionId)
    {
        GoalSession::create([
            'session_id' => $sessionId,
            'goal_id' => $goalId
        ]);
    }

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

    public function achievements($sessionId, $battleId)
    {
        $userId = \Auth::user()->id;
        $goalId = Goals::getCurrentGoal($userId);
        
        $achievements = Achievements::orderBy('sequence')->get();
        $mostPowefulPunch = $mostPowefulSpeed = 0;
        $mostPoweful = Sessions::getMostPowerfulPunchAndSpeed($sessionId);
        
        if ($mostPoweful) {
            $mostPowefulPunch = $mostPoweful->max_force;
            $mostPowefulSpeed = $mostPoweful->max_speed;
        }
        
        foreach ($achievements as $achievement) {
            switch ($achievement->id) {
                case 1:
                    $belts = Battles::getBeltCount(\Auth::user()->id);
                    if ($belts > 0) {
                        $achievementType = AchievementTypes::select('id')->where('achievement_id', $achievement->id)->first();

                        if ($achievementType->id) {
                            $beltsData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', $achievement->id)
                                    ->first();
                            if ($beltsData) {
                                if ($beltsData->metric_value < $belts) {
                                    $beltsData->metric_value = $belts;
                                    $beltsData->count = $belts;
                                    $beltsData->shared = false;
                                    $beltsData->session_id = $sessionId;
                                    $beltsData->awarded = true;
                                    $beltsData->save();
                                }
                            } else {
                                $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                            'achievement_id' => $achievement->id,
                                            'achievement_type_id' => $achievementType->id,
                                            'metric_value' => $belts,
                                            'count' => $belts,
                                            'awarded' => true,
                                            'session_id' => $sessionId]);
                            }
                        }
                    }
                    break;
                case 2:
                    $punchCount = Sessions::getPunchCount();
                    if ($punchCount > 0) {
                        $achievementType = AchievementTypes::select(\DB::raw('MAX(config) as max_val'), 'id')->where('config', '<=', $punchCount)
                                        ->where('achievement_id', $achievement->id)->first();

                        if ($achievementType->id) {
                            $getUserPunchData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                    ->where('user_id', $userId)
                                    ->where('achievement_id', $achievement->id)
                                    ->where('metric_value', $achievementType->max_val)
                                    ->first();

                            if (empty($getUserPunchData)) {
                                $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                            'achievement_id' => $achievement->id,
                                            'achievement_type_id' => $achievementType->id,
                                            'metric_value' => $achievementType->max_val,
                                            'count' => 1,
                                            'awarded' => true,
                                            'goal_id' => $goalId,
                                            'session_id' => $sessionId]);
                            }
                        }
                    }
                    break;
                case 3:
                    $mostPunches = 0;
                    if (empty($battleId)) {
                        $mostPunches = SessionRounds::getMostPunchesPerMinute($sessionId);
                        if ($mostPunches > 0) {
                            $achievementType = AchievementTypes::select(\DB::raw('MAX(config) as max_val'), 'id')->where('config', '<=', $mostPunches)
                                            ->where('achievement_id', $achievement->id)->first();
                            if ($achievementType->id) {
                                $mostPunchesData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $userId)
                                        ->where('achievement_id', $achievement->id)
                                        ->where('metric_value', $achievementType->max_val)
                                        ->first();
                                if (empty($mostPunchesData)) {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'awarded' => true,
                                                'achievement_type_id' => $achievementType->id,
                                                'count' => 1,
                                                'metric_value' => $achievementType->max_val,
                                                'goal_id' => $goalId,
                                                'session_id' => $sessionId]);
                                }
                            }
                        }
                    }
                    break;
                case 4:
                    $goal = Goals::getAccomplishedGoal();
                    if ($goal == 1) {
                        $achievementType = AchievementTypes::select('id')->where('achievement_id', $achievement->id)->first();

                        $goalData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                ->where('user_id', $userId)
                                ->where('achievement_id', $achievement->id)
                                ->first();
                        if ($goalData) {
                            $goalMatrix = $goalData->metric_value + 1;
                            $goalData->metric_value = $goalMatrix;
                            $goalData->session_id = $sessionId;
                            $goalData->count = $goalMatrix;
                            $goalData->shared = false;
                            $goalData->awarded = true;
                            $goalData->save();
                        } else {
                            $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $achievementType->id,
                                        'metric_value' => $goal,
                                        'awarded' => true,
                                        'count' => $goal,
                                        'session_id' => $sessionId]);
                        }
                    }
                    break;

                case 5:
                case 6:

                    $speedAndPunch = $mostPowefulSpeed;
                    if ($achievement->id == 5) {
                        $speedAndPunch = $mostPowefulPunch;
                    }
                    $achievementType = AchievementTypes::select('min', 'id')->where('achievement_id', $achievement->id)->first();
                    if ($speedAndPunch > $achievementType->min) {
                        $mostPowefulSpeedData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                ->where('user_id', $userId)
                                ->where('goal_id', $goalId)
                                ->where('achievement_id', $achievement->id)
                                ->first();
                        if ($mostPowefulSpeedData) {
                            if ($mostPowefulSpeedData->metric_value < $speedAndPunch) {
                                $mostPowefulSpeedData->metric_value = $speedAndPunch;
                                $mostPowefulSpeedData->count = 1;
                                $mostPowefulSpeedData->session_id = $sessionId;
                                $mostPowefulSpeedData->shared = false;
                                $mostPowefulSpeedData->awarded = true;
                                $mostPowefulSpeedData->save();
                            }
                        } else {
                            $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                        'count' => 1,
                                        'awarded' => true,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $achievementType->id,
                                        'metric_value' => $mostPowefulSpeed,
                                        'goal_id' => $goalId,
                                        'session_id' => $sessionId]);
                        }
                    }
                    break;

                case 7:
                    if ($battleId) {
                        $achievementType = AchievementTypes::select('id')->where('achievement_id', $achievement->id)->first();
                        $champion = Battles::getChampian($battleId);

                        $championData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                ->where('user_id', $userId)
                                ->where('achievement_id', $achievement->id)
                                ->first();
                        if ($champion > 0) {
                            if ($championData) {
                                if ($championData->metric_value < $belts) {
                                    $championData->metric_value = $champion;
                                    $championData->session_id = $sessionId;
                                    $championData->count = $champion;
                                    $championData->shared = false;
                                    $championData->awarded = true;
                                    $championData->save();
                                }
                            } else {
                                $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                            'achievement_id' => $achievement->id,
                                            'achievement_type_id' => $achievementType->id,
                                            'metric_value' => $champion,
                                            'count' => $champion,
                                            'awarded' => true,
                                            'session_id' => $sessionId]);
                            }
                        }
                    }
                    break;
            }
        }

        return UserAchievements::getSessionAchievements($userId, $sessionId);
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
                $score = \DB::table('session_round_punches')->select(\DB::raw('MIN(punch_duration) as min_punch_duration'))->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->pluck('min_punch_duration')->first();

                $raw = \DB::table('session_round_punches')->select('*')->where('punch_duration', $score)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->first();
                
                $distance = $raw->distance;
            break;

            // game_id = 2, then you can find max_speed from session table, and store it.
            case 2: // Speed
                $score = \DB::table('session_round_punches')->select(\DB::raw('MAX(speed) as max_speed'))->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->pluck('max_speed')->first();

                $raw = \DB::table('session_round_punches')->select('*')->where('speed', $score)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->first();
                
                $distance = $raw->distance;
            break;

            // game_id = 3, then calculate ppm according to punch count of session, and time of session (endtime - start time)
            // ref: SessionRounds -> getMostPunchesPerMinute()
            case 3: // Endurance
                $result = $currentSessionRoundsQuery->select(
                    \DB::raw('SUM(end_time - start_time) AS duration'),
                    \DB::raw('SUM(punches_count) as punches')
                )->first();

                $totalPPMOfRounds = $result->punches * 1000 * 60 / $result->duration;
                $roundsCountsOfSessions = $currentSessionQuery->count();

                // ppm of round1 + ppm of round2 + .... / round count of session
                $score = $totalPPMOfRounds / $roundsCountsOfSessions;

                $totalDistance = SessionRoundPunches::select(\DB::raw('SUM(distance) as total_distance'))->whereRaw('session_round_id IN (SELECT id FROM session_rounds WHERE session_id = ?)', $sessionId)->pluck('total_distance')->first();
                $totalPunches = SessionRoundPunches::whereRaw('session_round_id IN (SELECT id FROM session_rounds WHERE session_id = ?)', $sessionId)->count();

                $distance = $totalDistance / $totalPunches;
            break;

            // game_id == 4, then max_power will be stored.
            case 4: // Power
                $score = \DB::table('session_round_punches')->select(\DB::raw('MAX(`force`) as max_force'))->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->pluck('max_force')->first();

                $raw = \DB::table('session_round_punches')->select('*')->where('force', $score)->whereRaw('session_round_id IN ('. \DB::raw("{$currentSessionRoundsQuery->toSql()}")  .')' )->mergeBindings($currentSessionRoundsQuery)->first();
                
                $distance = $raw->distance;
            break;
        }

        $userGameLeaderboard = GameLeaderboard::where('user_id', \Auth::id())->where('game_id', $gameId)->first();

        if ($userGameLeaderboard) {
            // Reaction game, min value is better score
            $update = false; // Update or not

            if ($gameId == 1 && $userGameLeaderboard->score > $score) {
                $userGameLeaderboard->score = $score;
                $update = true;
            } elseif ($userGameLeaderboard->score < $score) {
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
