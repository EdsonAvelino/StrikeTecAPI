<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TrainingSessions;
use App\TrainingSessionRounds;
use App\TrainingSessionRoundsPunches;

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
     * @apiParam {Number} [training_type_id] Optional Training type id e.g. 1 = Quick Start, 2 = Round, 3 = Combo, 4 = Set, 5 = Workout
     * @apiParamExample {json} Input
     *    {
     *      "start_date": "09/11/2017",
     *      "end_date": "09/15/2017",
     *      "training_type_id": 1,
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
     *          "training_type_id": 1,
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
     *          "training_type_id": 1,
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
     *          "training_type_id": 1,
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
        $trainingTypeId = (int) $request->get('training_type_id');

        $startDate = date('Y-m-d', strtotime($startDate)) . ' 00:00:00';
        $endDate = date('Y-m-d', strtotime($endDate)) . ' 23:59:59';

        $_sessions = TrainingSessions::where('user_id', $userId);

        if (!empty($startDate) && !empty($endDate)) {
            $_sessions->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($trainingTypeId) {
            $_sessions->where('training_type_id', $trainingTypeId);
        }

        $sessions = [];

        foreach ($result = $_sessions->get() as $_session) {
            $temp = $_session->toArray();

            $roundIDs = \DB::select( \DB::raw("SELECT id FROM training_session_rounds WHERE training_session_id = $_session->id") );

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
     *          "training_type_id": 1,
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
     *          "training_session_id": 1,
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
     *          "training_session_id": 1,
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

        $session = TrainingSessions::where('id', $sessionId)->first();
        $rounds = TrainingSessionRounds::where('training_session_id', $sessionId)->get();

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
     *      { "training_type_id": 1, "start_time": 1505745766000, "end_time": "", "plan_id":-1 },
     *      { "training_type_id": 1, "start_time": 1505792485000, "end_time": "", "plan_id":-1 }
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
        $sessions = [];

        try {
            foreach ($data as $session) {
                $_session = TrainingSessions::create([
                        'user_id' => \Auth::user()->id,
                        'training_type_id' => $session['training_type_id'],
                        'start_time' => $session['start_time'],
                        'end_time' => $session['end_time'],
                        'plan_id' => $session['plan_id'],
                    ]);

                $sessions[] = ['start_time' => $_session->start_time];
            }

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
     *          "training_session_id": 1,
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
     *          "session_round_id": 1,
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
     *          "session_round_id": 1,
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
     *          "session_round_id": 1,
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
        $round = TrainingSessionRounds::where('id', $roundId)->first();
        $punches = TrainingSessionRoundsPunches::where('session_round_id', $roundId)->get();

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
     *      { "session_start_time": 1505745766000, "start_time": 1505745866000, "end_time": 1505745866000 },
     *      { "session_start_time": 1505792485000, "start_time": 1505792485080, "end_time": 1505792585000 }
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
                $sessionId = TrainingSessions::where('start_time', $round['session_start_time'])->first()->id;

                $_round = TrainingSessionRounds::create([
                        'training_session_id' => $sessionId,
                        'start_time' => $round['start_time'],
                        'end_time' => $round['end_time'],
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

        $avgForce = []; $avgForceCount = [];
        $avgSpeed = []; $avgSpeedCount = [];
        $maxSpeed = []; $maxForce = [];
        $punchDurations = [];

        $sessionRounds = [];
        $trainingSessions = [];

        try {
            foreach ($data as $punch) {
                $sessionRound = TrainingSessionRounds::where('start_time', $punch['round_start_time'])->first();

                // $sessionRoundId storing sessionRound->id for most usage
                $sessionRoundId = $sessionRound->id;

                // Generating total-session-rounds to loop through it in next step
                $sessionRounds[] = $sessionRoundId;

                // Storing force/speed data, grouping by sessionRoundId for ease of calculation
                $avgForce[$sessionRoundId] = ($avgForce[$sessionRoundId]) ?? 0;
                $avgForceCount[$sessionRoundId] = ($avgForceCount[$sessionRoundId]) ?? 0;
                
                $avgSpeed[$sessionRoundId] = ($avgSpeed[$sessionRoundId]) ?? 0;
                $avgSpeedCount[$sessionRoundId] = ($avgSpeedCount[$sessionRoundId]) ?? 0;

                // Store punch
                $_punch = TrainingSessionRoundsPunches::create([
                        'session_round_id' => $sessionRoundId,
                        'punch_time' => $punch['punch_time'],
                        'punch_duration' => $punch['punch_duration'],
                        'force' => $punch['force'],
                        'speed' => $punch['speed'],
                        'punch_type' => $punch['punch_type'],
                        'hand' => $punch['hand'],
                    ]);

                $punches[] = ['start_time' => $_punch->punch_time];

                // Sum of puches force & speed
                $avgForce[$sessionRoundId] += $punch['force'];
                $avgForceCount[$sessionRoundId] += 1;

                $avgSpeed[$sessionRoundId] += $punch['speed'];
                $avgSpeedCount[$sessionRoundId] += 1;

                // Storing max of force/speed into an array to get max of each easily
                $maxForce[$sessionRoundId][] = $punch['force'];
                $maxSpeed[$sessionRoundId][] = $punch['speed'];

                // Storing all punches' punch_duration into an array
                // to get best-time (min of puch_duration)
                // and avg-time = sum of punch_duration / total num of punch_duration
                $punchDurations[$sessionRoundId][] = $punch['punch_duration'];
            }

            // ------------------------------------------------------------------
            // CALCULATIONS & Store its results to appropriate rounds
            // ------------------------------------------------------------------
            
            // Removes duplicate values and loops through list of session-rounds
            foreach (array_unique($sessionRounds) as $sessionRoundId) {
                $sessionRound = TrainingSessionRounds::where('id', $sessionRoundId)->first();

                $trainingSessions[] = $sessionRound->training_session_id;

                // Round wise avg / max calculation
                $_avgSpeed = $avgSpeed[$sessionRoundId] / $avgSpeedCount[$sessionRoundId];
                $_avgForce = $avgForce[$sessionRoundId] / $avgForceCount[$sessionRoundId];

                $sessionRound->avg_speed = $_avgSpeed;
                $sessionRound->avg_force = $_avgForce;

                // Note: avgForceCount & avgSpeedCount both will have same values of punches count
                // So using one of them for  punches_count field
                $sessionRound->punches_count = $avgForceCount[$sessionRoundId];
                
                $sessionRound->max_speed = max($maxSpeed[$sessionRoundId]);
                $sessionRound->max_force = max($maxForce[$sessionRoundId]);
                
                $sessionRound->best_time = min($punchDurations[$sessionRoundId]);
                
                $sessionRound->avg_time = array_sum($punchDurations[$sessionRoundId]) / count($punchDurations[$sessionRoundId]);

                $sessionRound->save();
            }

            // Removes duplicate values of trainingSessions and loops through it
            foreach (array_unique($trainingSessions) as $trainingSessionId) {
                // echo "\nID:".$trainingSessionId;
                $trainingSession = TrainingSessions::where('id', $trainingSessionId)->first();

                $stats = collect(
                        \DB::select("SELECT
                        SUM(`speed`) AS total_speed, SUM(`force`) AS total_force,
                        COUNT(speed) AS speed_count, COUNT(`force`) AS force_count,
                        MAX(`speed`) AS max_speed,  MAX(`force`) AS max_force
                        FROM `training_session_rounds_punches`
                        WHERE session_round_id IN (
                            SELECT id FROM training_session_rounds
                            WHERE training_session_id = $trainingSessionId
                        )")
                    )->first();

                $trainingSession->avg_speed = $stats->total_speed / $stats->speed_count;
                $trainingSession->avg_force = $stats->total_force / $stats->force_count;
                $trainingSession->punches_count = $stats->force_count;
                $trainingSession->max_speed = $stats->max_speed;
                $trainingSession->max_force = $stats->max_force;

                $trainingSession->save();
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
}