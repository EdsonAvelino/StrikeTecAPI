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
use App\User;
use Carbon\Carbon;

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
     *      "user_id": 54
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
     * @apiVersion 1.0.0
     */
    public function getSessions(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $trainingTypeId = (int) $request->get('type_id');

        // $startDate = ($startDate) ? date('Y-m-d 00:00:00', (int) $startDate) : null;
        // $endDate = ($endDate) ? date('Y-m-d 23:59:59', (int) $endDate) : null;

        $startDate = ($startDate) ? $startDate * 1000 : null;
        $endDate = ($endDate) ? ($endDate * 1000) - 1 : null;

        $userId = $request->get('user_id') ?? \Auth::user()->id;

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
     * @apiVersion 1.0.0
     */
    public function getSession($sessionId)
    {
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
     * @apiVersion 1.0.0
     */
    public function getSessionForComparison(Request $request)
    {
        $sessionId = $request->get('session_id');
        $typeId = $request->get('type_id');

        $userId = $request->get('user_id') ?? \Auth::user()->id;

        $_sessions = Sessions::where(function($query) use ($sessionId) {
                            $query->where('id', $sessionId)->orWhere('id', '<', $sessionId);
                        })->where('type_id', $typeId)->where(function($query) {
                            $query->whereNull('is_archived')->orWhere('is_archived', 0);
                        })->where('user_id', $userId)
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
     */
    public function storeSessions(Request $request)
    {
        $data = $request->get('data');
        // $userId = $request->get('user_id') ?? \Auth::user()->id;

        $sessions = []; //Will be use for response
        $sessionIdArr = [];

        if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
            \Log::info('Api Url {post} /user/training/sessions  (Training - Upload sessions)');
            \Log::info('The Request Data - ' , $data);
            \Log::info('Auth User ID - ' . \Auth::user()->id);
        }

        $gameSession = false;

        // $sessionCount = $sessionPunchesCount = 0;
        
        foreach ($data as $session) {
            $userId = ($session['user_id']) ?? \Auth::user()->id;
            $weight = ($session['user_id']) ?? \Auth::user()->weight;

            try {
                $sessionStartTime = $session['start_time'];
                // $sessionPunchesCount += $session['punches_count'];
                // $sessionCount++;
                $maxForceArr[] = $session['max_force'];
                $maxSpeedArr[] = $session['max_speed'];

                // Checking if session already exists
                $_session = Sessions::where('start_time', $session['start_time'])->first();

                if (!$_session) {
                    $newSession = [
                        'user_id' => $userId,
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
                        'best_time' => $session['best_time'],
                        'weight' => $weight
                    ];
                    
                    $_session = Sessions::create($newSession);
                    
                    SessionRounds::where('session_start_time', $_session->start_time)->update(['session_id' => $_session->id]);
                    
                    // Update battle details, if any
                    if ($_session->battle_id) {
                        $this->updateBattle($_session->battle_id);
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
                    SessionRounds::where('session_start_time', $_session->start_time)->update(['session_id' => $_session->id]);
                }
                // Process through achievements (badges) and assign 'em to user
                
                // skipping Achievements for now as they are taking much time
                // the logic of achievements calcuation should be revised
                // $achievements = $this->achievements($_session->id, $_session->battle_id);
                // Generating sessions' list for response
                $sessions[] = [
                    'session_id' => $_session->id,
                    'start_time' => $_session->start_time,
                    'achievements' => $this->achievements($_session->id, $_session->battle_id)
                    //'achievements' => []
                ];
                $sessionIdArr[] = $_session->id;
                
                // Sending response back if session is of game
                if ($gameSession) {
                    // return response()->json([
                    //     'error' => 'false',
                    //     'message' => 'Training sessions saved successfully',
                    //     'data' => $sessions
                    // ]);
                    continue;
                }

            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'true',
                    'message' => $e->getMessage()
                ]);
            }

            try {
                // User's total sessions count
                //$sessionsCount = Sessions::where('user_id', \Auth::user()->id)->count();
                //$punchesCount = Sessions::select(\DB::raw('SUM(punches_count) as punches_count'))->where('user_id', \Auth::user()->id)->pluck('punches_count')->first();
                // Create / Update Leaderboard entry for this user
                $leaderboardStatus = Leaderboard::where('user_id', $userId)->first();
                
                    // Set all old averate data to 0
                $oldAvgSpeed = $oldAvgForce = $oldPunchesCount = $oldTotalDaysTrained = 0;
                 
                $oldAvgSpeed = $leaderboardStatus->avg_speed;
                $oldAvgForce = $leaderboardStatus->avg_force;
                $oldSessionCount = $leaderboardStatus->sessions_count;
                $oldPunchesCount = $leaderboardStatus->punches_count;
                $oldTotalDaysTrained = $leaderboardStatus->total_days_trained;
                $oldMaxSpeed = $leaderboardStatus->max_speed;
                $oldMaxForce = $leaderboardStatus->max_force;
    
                $maxSpeedArr[] = $oldMaxSpeed;
                $maxMaxForce[] = $oldMaxForce;
    
                // $leaderboardStatus->sessions_count = $oldSessionCount + $sessionCount;
                // $leaderboardStatus->punches_count = $oldPunchesCount + $sessionPunchesCount;
                $leaderboardStatus->sessions_count = $oldSessionCount + 1;
                $leaderboardStatus->punches_count = $oldPunchesCount + $session['punches_count'];
                
                $sessionDateTime = Carbon::parse(date('Y-m-d H:i:s', $sessionStartTime / 1000));
                \Log::info("session time:", [$sessionDateTime]);
                
                if (!empty($leaderboardStatus->last_training_date)) {
                    $lastTrainedDateTime = Carbon::parse($leaderboardStatus->last_training_date);
                    
                    if ($lastTrainedDateTime->toDateString() != $sessionDateTime->toDateString()) {
                        $leaderboardStatus->total_days_trained = $oldTotalDaysTrained + 1;
                    }
                }
                else {
                    $leaderboardStatus->total_days_trained = $oldTotalDaysTrained + 1;
                }

                $leaderboardStatus->last_training_date = $sessionDateTime;

                $leaderboardStatus->save();
    
                // Formula
                // (old avg speed x old total punches + session1's speed x session1's punch count + session2's speed x session2's punch count) / (old total punches + session1's punch count + session2's punchcount)
                // $avgSpeedData[] = $oldAvgSpeed * $oldPunchesCount;
                // $avgForceData[] = $oldAvgForce * $oldPunchesCount;
                // $division = $oldPunchesCount;
                // // foreach ($data as $session) {
                // //     $avgSpeedData[] = $session['avg_speed'] * $session['punches_count'];
                // //     $avgForceData[] = $session['avg_force'] * $session['punches_count'];
                // //     $division += $session['punches_count'];
                // // }
                // $avgSpeedData[] = $session['avg_speed'] * $session['punches_count'];
                // $avgForceData[] = $session['avg_force'] * $session['punches_count'];
                // $division += $session['punches_count'];
            
                // if ($division !== 0) {
                //     $leaderboardStatus->avg_speed = array_sum($avgSpeedData) / $division;
                //     $leaderboardStatus->avg_force = array_sum($avgForceData) / $division;
                // }
                $oldTotalSpeedData = $oldAvgSpeed * $oldPunchesCount;
                $oldTotalForceData = $oldAvgForce * $oldPunchesCount;
                $curSpeedData = $session['avg_speed'] * $session['punches_count'];
                $curForceData = $session['avg_force'] * $session['punches_count'];
                $totalCount = $oldPunchesCount + $session['punches_count'];
            
                if ($totalCount !== 0) {
                    $leaderboardStatus->avg_speed = ($oldTotalSpeedData + $curSpeedData) / $totalCount;
                    $leaderboardStatus->avg_force = ($oldTotalForceData + $curForceData) / $totalCount;
                }
    
                /*$temp = SessionRounds::select(
                                        \DB::raw('MAX(max_speed) as max_speed'), \DB::raw('MAX(max_force) as max_force')
                                )
                                ->whereRaw('session_id IN (SELECT id from sessions WHERE user_id = ?)', [\Auth::user()->id])->first();*/
                
                // $sessionIds = join("','",$sessionIdArr);
                $sessionIds = [$_session->id];
                $sessionIds = join("','",$sessionIdArr);
    
                // \Log::info('The Request Data - ' . $sessionIds);
                // \Log::info('The Request Data - ' . $userId);

                $temp = SessionRounds::select(\DB::raw('SUM(pause_duration) as pause_duration'))
                                ->whereRaw('session_id IN ("'.$sessionIds.'")', [$userId])->first();
    
                $pauseDuration = $temp->pause_duration;                            
    
                $leaderboardStatus->max_speed = max($maxSpeedArr);
                $leaderboardStatus->max_force = max($maxForceArr);
                
                //$totalTimeTrained = Sessions::select(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(start_time / 1000), FROM_UNIXTIME(end_time / 1000))) AS duration_in_sec'))->groupBy('user_id')->where('user_id', \Auth::user()->id)->pluck('duration_in_sec')->first();
                $totalTimeTrained = SessionRounds::select(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(start_time / 1000), FROM_UNIXTIME(end_time / 1000))) AS duration_in_sec'))->whereRaw('session_id IN ("'.$sessionIds.'")')->first();
    
                $leaderboardStatus->total_time_trained = $leaderboardStatus->total_time_trained + (abs($totalTimeTrained->duration_in_sec) * 1000) - $pauseDuration;
                $leaderboardStatus->save();
                
            } catch(\Exception $e) {
                return response()->json([
                    'error' => 'true',
                    'message' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'error' => 'false',
            'message' => 'Training sessions saved successfully',
            'data' => $sessions
        ]);
        
    }

    /**
     * @api {patch} /user/training/sessions/<session_id>/archive Archive session
     */
    public function archiveSession($sessionId)
    {
        $sessionId = (int) $sessionId;
        
        // $session = Sessions::where('id', $sessionId)->where('user_id', \Auth::id())->first();
        $session = Sessions::where('id', $sessionId)->first();

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
     */
    public function getSessionsRound($roundId)
    {
        if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
            \Log::info('Api Url {get} /user/training/sessions/rounds/{round_id}  (Get rounds and its punches)');
            \Log::info('The Round ID - ' . $roundId);
            \Log::info('Auth User ID - ' . \Auth::user()->id);
        }

        $rounds = SessionRounds::where('id', $roundId)->get();

        if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
            \Log::info('The Round Data - ' , $rounds->toArray());
            \Log::info('Auth User ID - ' . \Auth::user()->id);
        }

        // If round not found, it will return null
        if (empty($rounds)) {
            return response()->json([
                        'error' => 'false',
                        'message' => '',
                        'round' => null,
                        'punches' => null
            ]);
        }

        $punches = SessionRoundPunches::where('session_round_id', $roundId)->get();

        if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
            \Log::info('The Round Punches Data - ' , $punches->toArray());
            \Log::info('Auth User ID - ' . \Auth::user()->id);
        }

        return response()->json([
                    'error' => 'false',
                    'message' => '',
                    'round' => $rounds->first(),
                    'punches' => $punches->toArray()
        ]);
    }

    /**
     * @api {post} /user/training/sessions/rounds Upload sessions' rounds
     */
    public function storeSessionsRounds(Request $request)
    {
        $data = $request->get('data');
        $rounds = [];

        if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
            \Log::info('Api Url {post} /user/training/sessions/rounds  (Training - Upload sessions rounds)');
            \Log::info('The Request Data - ' , $data); 
            \Log::info('Auth User ID - ' . \Auth::user()->id);
        }

        try {
            foreach ($data as $round) {
                // Checking if round already exists
                $testRound = SessionRounds::where('start_time', $round['start_time'])->where('session_start_time', $round['session_start_time']);
                
                $_round = $testRound->first();

                if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
                    \Log::info('storeSessionsRounds() Session Start Time - ' . $round['session_start_time']);
                    \Log::info('Count For Get sessions Rounds - '. $testRound->count());
                }

                if (!$_round) {
                    $_round = SessionRounds::create([
                        'session_start_time' => $round['session_start_time'],
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

                    if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
                        \Log::info('Create NEW Session Round Data - ' , [$_round]);
                    }
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
     */
    public function storeSessionsRoundsPunches(Request $request)
    {
        $data = $request->get('data');
        
        $punches = [];
        $_newPunches = [];

        if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
            \Log::info('Api Url {post} /user/training/sessions/rounds/punches  (Training - Training - Upload rounds punches)');
            \Log::info('The Request Data - ' , $data);
            \Log::info('Auth User ID - ' . \Auth::user()->id);
        }

        /*
        foreach ($data as $punch) {

            $sessionRound = SessionRounds::where('start_time', $punch['round_start_time'])->first();

            if ($sessionRound) {

                // Check if punches already exists
                $_punch = SessionRoundPunches::where('punch_time', $punch['punch_time'])->where('session_round_id', $sessionRound->id)->first();

                if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
                    \Log::info('Count For Get sessions Rounds Punches  - '. $testPunches->count());
                    \Log::info('storeSessionsRoundsPunches() Punch Time - ' . $punch['punch_time']);
                }

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

                    if (\Auth::user()->id == 342 || \Auth::user()->id == 361) {
                        \Log::info('Created NEW Round Punches data- '.$_punch);
                    }
                }

                $punches[] = ['start_time' => $_punch->punch_time];
            }
        }
        */

        foreach ($data as $punch) {
            $arrRoundStartTime[] = $punch['round_start_time'];
            $arrPunchTime[] = $punch['punch_time'];
            $punches[] = ['start_time' => $punch['punch_time']];
        }

        $sessionRounds = SessionRounds::whereIn('start_time', array_unique($arrRoundStartTime))->get();
        $roundPunches = SessionRoundPunches::whereIn('punch_time', array_unique($arrPunchTime))->get();

        $arrRoundStartTime = $arrRoundID = $arrPunchTime = $arrPunchID = [];
        
        foreach ($sessionRounds as $sessionRound) {
            $arrRoundID[] = $sessionRound->id;
            $arrRoundStartTime[] = $sessionRound->start_time;
        }

        foreach ($roundPunches as $roundPunch) {
            $arrPunchID[] = $roundPunch->id;
            $arrPunchTime[] = $roundPunch->punch_time;
        }

        foreach ($data as $punch) {
            if (in_array($punch['round_start_time'], $arrRoundStartTime)) {
                $roundID = $arrRoundID[array_search($punch['round_start_time'], $arrRoundStartTime)];

                if (in_array($punch['punch_time'], $arrPunchTime)) {
                    $roundPunch = $roundPunches[array_search($punch['round_start_time'], $arrRoundStartTime)];

                    if ($roundPunch->session_round_id == $roundID) {
                        continue;
                    }
                }

                if (isset($punch['is_correct'])) {
                    $isCorrect = filter_var($punch['is_correct'], FILTER_VALIDATE_BOOLEAN);
                }

                $createdAt = \Carbon\Carbon::now();

                $_newPunches[] = [
                    'session_round_id' => $roundID,
                    'punch_time' => $punch['punch_time'],
                    'punch_duration' => $punch['punch_duration'],
                    'force' => $punch['force'],
                    'speed' => $punch['speed'],
                    'punch_type' => strtoupper($punch['punch_type']),
                    'hand' => strtoupper($punch['hand']),
                    'distance' => $punch['distance'],
                    'is_correct' => $isCorrect,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt
                ];
            }
        }

        try {
            SessionRoundPunches::insert($_newPunches);

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
     * @apiVersion 1.0.0
     */
    public function tips(Request $request)
    {
        $sessionId = (int) $request->get('session_id');

        $userId = $request->get('client_id') ?? \Auth::user()->id;

        $data = $this->getTipsData($sessionId, $userId);

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
    private function getTipsData($sessionId, $userId)
    {
        $session = Sessions::select('id', 'plan_id', 'type_id', 'avg_speed', 'avg_force')
                        ->where(function ($query) use($sessionId) {
                            $query->where('id', $sessionId)->where('user_id', $userId);
                        })->first();

        if ($session) {
            $sessionType = $session->type_id;
            $sessionPlan = $session->plan_id;
            $sessionIds = $data = $force = [];

            if ($sessionType == 1 or $sessionType == 2) {
                $sessionIds = Sessions::select('id')->where('user_id', $userId)
                        ->where('type_id', $sessionType)->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->get()->toArray();

                $sessionData = Sessions::select(
                                    \DB::raw('MAX(avg_speed) as highest_speed'), \DB::raw('MIN(avg_speed) as lowest_speed'), \DB::raw('MAX(avg_force) as highest_force'), \DB::raw('MIN(avg_force) as lowest_force')
                                )
                                ->where('user_id', $userId)
                                ->where('type_id', $sessionType)->where(function ($query) {
                                    $query->whereNull('battle_id')->orWhere('battle_id', '0');
                                })->first();
            }
            else {
                $sessionIds = Sessions::select('id')->where('user_id', $userId)
                        ->where(function ($query)use($sessionType, $sessionPlan) {
                            $query->where('type_id', $sessionType)->where('plan_id', $sessionPlan);
                        })->where(function ($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->get()->toArray();

                $sessionData = Sessions::select(
                                    \DB::raw('MAX(avg_speed) as highest_speed'), \DB::raw('MIN(avg_speed) as lowest_speed'), \DB::raw('MAX(avg_force) as highest_force'), \DB::raw('MIN(avg_force) as lowest_force')
                                )
                                ->where('user_id', $userId)
                                ->where(function ($query)use($sessionType, $sessionPlan) {
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

    public function autoupdate(Request $request)
    {
        $users = User::where('email', 'like', '%striketec.com%')->get();
        $datas = [];
        // $user = User::where('email', 'test@test.com')->first();
        foreach ($users as $user) {

            $sessions = []; //Will be use for response
            $sessionIdArr = [];

            $data = [];
            $sample_count = rand(1, 5);
            $timestamp = Carbon::now()->timestamp;
            $speed = rand(14, 20);
            $force = rand(300, 500);
            $sample = [
                'user_id' => $user->id,
                "type_id" => 1,
                "battle_id" => 0,
                "game_id" => 0,
                "start_time" => ($timestamp - rand(5, 15) * 3600) * 1000,
                "end_time" => $timestamp * 1000,
                "plan_id" => -1,
                "avg_speed" => $speed,
                "avg_force" => $force,
                "punches_count" => rand(4000, 6000),
                "max_force" => $force,
                "max_speed" => $speed,
                "best_time" => rand(1, 3) / 10.0
            ];
            $data[] = $sample;
            $datas[] = $data;
            $gameSession = false;
            foreach ($data as $session) {
                $userId = $user->id;
                $weight = $user->weight;
                try {
                    $sessionStartTime = $session['start_time'];
                    // $sessionPunchesCount += $session['punches_count'];
                    // $sessionCount++;
                    $maxForceArr[] = $session['max_force'];
                    $maxSpeedArr[] = $session['max_speed'];

                    // Checking if session already exists
                    $_session = Sessions::where('start_time', $session['start_time'])->first();

                    if (!$_session) {
                        $newSession = [
                            'user_id' => $userId,
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
                            'best_time' => $session['best_time'],
                            'weight' => $weight
                        ];
                        
                        $_session = Sessions::create($newSession);
                        SessionRounds::where('session_start_time', $_session->start_time)->update(['session_id' => $_session->id]);
                        
                        // Update battle details, if any
                        if ($_session->battle_id) {
                            $this->updateBattle($_session->battle_id);
                        }
                        // Game stuff
                        elseif ($_session->game_id) {
                            $gameSession = true;
                            $this->updateGameLeaderboard($_session->game_id, $_session->id);
                        }
                        // Goal updates
                        else {
                            // $this->updateGoal($_session);
                        }
                    } else {
                        SessionRounds::where('session_start_time', $_session->start_time)->update(['session_id' => $_session->id]);
                    }
                    // Process through achievements (badges) and assign 'em to user
                    
                    // skipping Achievements for now as they are taking much time
                    // the logic of achievements calcuation should be revised
                    // $achievements = $this->achievements($_session->id, $_session->battle_id);
                    // Generating sessions' list for response
                    $sessions[] = [
                        'session_id' => $_session->id,
                        'start_time' => $_session->start_time,
                        // 'achievements' => $this->achievements($_session->id, $_session->battle_id)
                        //'achievements' => []
                    ];
                    $sessionIdArr[] = $_session->id;
                    
                    // Sending response back if session is of game
                    if ($gameSession) {
                        // return response()->json([
                        //     'error' => 'false',
                        //     'message' => 'Training sessions saved successfully',
                        //     'data' => $sessions
                        // ]);
                        continue;
                    }

                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'true',
                        'message' => $e->getMessage()
                    ]);
                }

                try {
                    // User's total sessions count
                    //$sessionsCount = Sessions::where('user_id', \Auth::user()->id)->count();
                    //$punchesCount = Sessions::select(\DB::raw('SUM(punches_count) as punches_count'))->where('user_id', \Auth::user()->id)->pluck('punches_count')->first();
                    // Create / Update Leaderboard entry for this user
                    $leaderboardStatus = Leaderboard::where('user_id', $userId)->first();
                    
                        // Set all old averate data to 0
                    $oldAvgSpeed = $oldAvgForce = $oldPunchesCount = $oldTotalDaysTrained = 0;
                    
                    $oldAvgSpeed = $leaderboardStatus->avg_speed;
                    $oldAvgForce = $leaderboardStatus->avg_force;
                    $oldSessionCount = $leaderboardStatus->sessions_count;
                    $oldPunchesCount = $leaderboardStatus->punches_count;
                    $oldTotalDaysTrained = $leaderboardStatus->total_days_trained;
                    $oldMaxSpeed = $leaderboardStatus->max_speed;
                    $oldMaxForce = $leaderboardStatus->max_force;
        
                    $maxSpeedArr[] = $oldMaxSpeed;
                    $maxMaxForce[] = $oldMaxForce;
        
                    // $leaderboardStatus->sessions_count = $oldSessionCount + $sessionCount;
                    // $leaderboardStatus->punches_count = $oldPunchesCount + $sessionPunchesCount;
                    $leaderboardStatus->sessions_count = $oldSessionCount + 1;
                    $leaderboardStatus->punches_count = $oldPunchesCount + $session['punches_count'];
                    
                    $sessionDateTime = Carbon::parse(date('Y-m-d H:i:s', $sessionStartTime / 1000));
                    \Log::info("session time:", [$sessionDateTime]);
                    
                    if (!empty($leaderboardStatus->last_training_date)) {
                        $lastTrainedDateTime = Carbon::parse($leaderboardStatus->last_training_date);
                        
                        if ($lastTrainedDateTime->toDateString() != $sessionDateTime->toDateString()) {
                            $leaderboardStatus->total_days_trained = $oldTotalDaysTrained + 1;
                        }
                    }
                    else {
                        $leaderboardStatus->total_days_trained = $oldTotalDaysTrained + 1;
                    }

                    $leaderboardStatus->last_training_date = $sessionDateTime;

                    $leaderboardStatus->save();
        
                    // Formula
                    // (old avg speed x old total punches + session1's speed x session1's punch count + session2's speed x session2's punch count) / (old total punches + session1's punch count + session2's punchcount)
                    // $avgSpeedData[] = $oldAvgSpeed * $oldPunchesCount;
                    // $avgForceData[] = $oldAvgForce * $oldPunchesCount;
                    // $division = $oldPunchesCount;
                    // // foreach ($data as $session) {
                    // //     $avgSpeedData[] = $session['avg_speed'] * $session['punches_count'];
                    // //     $avgForceData[] = $session['avg_force'] * $session['punches_count'];
                    // //     $division += $session['punches_count'];
                    // // }
                    // $avgSpeedData[] = $session['avg_speed'] * $session['punches_count'];
                    // $avgForceData[] = $session['avg_force'] * $session['punches_count'];
                    // $division += $session['punches_count'];
                
                    // if ($division !== 0) {
                    //     $leaderboardStatus->avg_speed = array_sum($avgSpeedData) / $division;
                    //     $leaderboardStatus->avg_force = array_sum($avgForceData) / $division;
                    // }
                    $oldTotalSpeedData = $oldAvgSpeed * $oldPunchesCount;
                    $oldTotalForceData = $oldAvgForce * $oldPunchesCount;
                    $curSpeedData = $session['avg_speed'] * $session['punches_count'];
                    $curForceData = $session['avg_force'] * $session['punches_count'];
                    $totalCount = $oldPunchesCount + $session['punches_count'];
                
                    if ($totalCount !== 0) {
                        $leaderboardStatus->avg_speed = ($oldTotalSpeedData + $curSpeedData) / $totalCount;
                        $leaderboardStatus->avg_force = ($oldTotalForceData + $curForceData) / $totalCount;
                    }
        
                    /*$temp = SessionRounds::select(
                                            \DB::raw('MAX(max_speed) as max_speed'), \DB::raw('MAX(max_force) as max_force')
                                    )
                                    ->whereRaw('session_id IN (SELECT id from sessions WHERE user_id = ?)', [\Auth::user()->id])->first();*/
                    
                    // $sessionIds = join("','",$sessionIdArr);
                    $sessionIds = [$_session->id];
                    $sessionIds = join("','",$sessionIdArr);
        
                    // \Log::info('The Request Data - ' . $sessionIds);
                    // \Log::info('The Request Data - ' . $userId);

                    $temp = SessionRounds::select(\DB::raw('SUM(pause_duration) as pause_duration'))
                                    ->whereRaw('session_id IN ("'.$sessionIds.'")', [$userId])->first();
        
                    $pauseDuration = $temp->pause_duration;                            
        
                    $leaderboardStatus->max_speed = max($maxSpeedArr);
                    $leaderboardStatus->max_force = max($maxForceArr);
                    
                    //$totalTimeTrained = Sessions::select(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(start_time / 1000), FROM_UNIXTIME(end_time / 1000))) AS duration_in_sec'))->groupBy('user_id')->where('user_id', \Auth::user()->id)->pluck('duration_in_sec')->first();
                    $totalTimeTrained = SessionRounds::select(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(start_time / 1000), FROM_UNIXTIME(end_time / 1000))) AS duration_in_sec'))->whereRaw('session_id IN ("'.$sessionIds.'")')->first();
        
                    $leaderboardStatus->total_time_trained = $leaderboardStatus->total_time_trained + (abs($totalTimeTrained->duration_in_sec) * 1000) - $pauseDuration;
                    $leaderboardStatus->save();
                    
                } catch(\Exception $e) {
                    return response()->json([
                        'error' => 'true',
                        'message' => $e->getMessage()
                    ]);
                }
            }
        }

        return response()->json([
            'error' => 'false',
            'message' => 'Training sessions saved successfully',
            'data' => $datas
        ]);
    }

    public function test(Request $request)
    {
        // $user = User::select('email')->get();
        // return $user;

        $user = User::where('email', 'test@test.com')->first();
        // if ($user) {
        //     $startDate = '2019-6-5';
        //     $endDate = '2019-7-7';
        //     $sessions = Sessions::where('created_at', '>=', $startDate)
        //         ->where('created_at', '<=', $endDate)
        //         ->get();
        // }
        
        $rounds = [];
        foreach($sessions as $session) {
            $round = SessionRounds::where('session_id', $session->id)->get();
            $rounds[] = $round;
        }
        
        $punchRound = SessionRoundPunches::where('session_round_id', 4860)->get();
        return $punchRound;


        $userId = 5;
        $sessions = [172, 173, 174];
        foreach($sessions as $sessionId) {
            $session = Sessions::where('id', $sessionId)->first();
            $_session = $session;
            $sessionStartTime = $session['start_time'];
            try {
                $leaderboardStatus = Leaderboard::where('user_id', $userId)->first();
                
                    // Set all old averate data to 0
                $oldAvgSpeed = $oldAvgForce = $oldPunchesCount = $oldTotalDaysTrained = 0;
                
                $oldAvgSpeed = $leaderboardStatus->avg_speed;
                $oldAvgForce = $leaderboardStatus->avg_force;
                $oldSessionCount = $leaderboardStatus->sessions_count;
                $oldPunchesCount = $leaderboardStatus->punches_count;
                $oldTotalDaysTrained = $leaderboardStatus->total_days_trained;
                $oldMaxSpeed = $leaderboardStatus->max_speed;
                $oldMaxForce = $leaderboardStatus->max_force;

                $maxSpeedArr[] = $oldMaxSpeed;
                $maxMaxForce[] = $oldMaxForce;

                // $leaderboardStatus->sessions_count = $oldSessionCount + $sessionCount;
                // $leaderboardStatus->punches_count = $oldPunchesCount + $sessionPunchesCount;
                $leaderboardStatus->sessions_count = $oldSessionCount + 1;
                $leaderboardStatus->punches_count = $oldPunchesCount + $session['punches_count'];
                
                $sessionDateTime = Carbon::parse(date('Y-m-d H:i:s', $sessionStartTime / 1000));
                \Log::info("session time:", [$sessionDateTime]);
                
                if (!empty($leaderboardStatus->last_training_date)) {
                    $lastTrainedDateTime = Carbon::parse($leaderboardStatus->last_training_date);
                    
                    if ($lastTrainedDateTime->toDateString() != $sessionDateTime->toDateString()) {
                        $leaderboardStatus->total_days_trained = $oldTotalDaysTrained + 1;
                    }
                }
                else {
                    $leaderboardStatus->total_days_trained = $oldTotalDaysTrained + 1;
                }

                $leaderboardStatus->last_training_date = $sessionDateTime;

                //$leaderboardStatus->save();

                // Formula
                // (old avg speed x old total punches + session1's speed x session1's punch count + session2's speed x session2's punch count) / (old total punches + session1's punch count + session2's punchcount)
                $oldTotalSpeedData = $oldAvgSpeed * $oldPunchesCount;
                $oldTotalForceData = $oldAvgForce * $oldPunchesCount;
                $curSpeedData = $session['avg_speed'] * $session['punches_count'];
                $curForceData = $session['avg_force'] * $session['punches_count'];
                $totalCount = $oldPunchesCount + $session['punches_count'];
            
                if ($totalCount !== 0) {
                    $leaderboardStatus->avg_speed = ($oldTotalSpeedData + $curSpeedData) / $totalCount;
                    $leaderboardStatus->avg_force = ($oldTotalForceData + $curForceData) / $totalCount;
                }
                var_dump($leaderboardStatus->avg_force);

                /*$temp = SessionRounds::select(
                                        \DB::raw('MAX(max_speed) as max_speed'), \DB::raw('MAX(max_force) as max_force')
                                )
                                ->whereRaw('session_id IN (SELECT id from sessions WHERE user_id = ?)', [\Auth::user()->id])->first();*/
                
                // $sessionIds = join("','",$sessionIdArr);
                // $sessionIds = [$_session->id];
                // $sessionIds = join("','",$sessionIdArr);

                // // \Log::info('The Request Data - ' . $sessionIds);
                // // \Log::info('The Request Data - ' . $userId);

                // $temp = SessionRounds::select(\DB::raw('SUM(pause_duration) as pause_duration'))
                //                 ->whereRaw('session_id IN ("'.$sessionIds.'")', [$userId])->first();

                // $pauseDuration = $temp->pause_duration;                            

                // $leaderboardStatus->max_speed = max($maxSpeedArr);
                // $leaderboardStatus->max_force = max($maxForceArr);
                
                // //$totalTimeTrained = Sessions::select(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(start_time / 1000), FROM_UNIXTIME(end_time / 1000))) AS duration_in_sec'))->groupBy('user_id')->where('user_id', \Auth::user()->id)->pluck('duration_in_sec')->first();
                // $totalTimeTrained = SessionRounds::select(\DB::raw('SUM(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(start_time / 1000), FROM_UNIXTIME(end_time / 1000))) AS duration_in_sec'))->whereRaw('session_id IN ("'.$sessionIds.'")')->first();

                // $leaderboardStatus->total_time_trained = $leaderboardStatus->total_time_trained + (abs($totalTimeTrained->duration_in_sec) * 1000) - $pauseDuration;
                // // $leaderboardStatus->save();
                
            } catch(\Exception $e) {
                return response()->json([
                    'error' => 'true',
                    'message' => $e->getMessage()
                ]);
            }
        }
        return;

        return $this->achievements(134, 0);
    }
    
    public function IsInThisWeek($dateTime)
    {
    }
    
    public function achievements($sessionId, $battleId)
    {
       $userId = \Auth::user()->id;
        
        $users = User::where('id', $userId)->first();
        $gender = $users->gender;
        
        if ($gender == NULL) {
            $gender = 'male';
        }        

        $goalId = Goals::getCurrentGoal($userId);
        
        $achievements = Achievements::orderBy('sequence')->get();
        $mostPowefulPunch = $mostPowefulSpeed = 0;
        $mostPoweful = Sessions::getMostPowerfulPunchAndSpeed($sessionId);
        
        if ($mostPoweful) {
            $mostPowefulPunch = $mostPoweful->max_force;
            $mostPowefulSpeed = $mostPoweful->max_speed;
        }

        if(strtolower(date('l'))=='monday'){
            $perviousMonday = strtotime('today');
        }
        else{
            $perviousMonday = strtotime('Previous Monday');
        }
        if(strtolower(date('l'))=='monday'){
            $timeThisMonday = date('Y-m-d');
        }
        else{
            $timeThisMonday = date('Y-m-d', strtotime('Previous Monday'));
        }
        
        $newArchievements = [];
        foreach ($achievements as $achievement) {
            switch ($achievement->id) {
                case 1:
                    //\Log::info('in 1');
                    if($battleId){
                        $battle = Battles::where('id', $battleId)->first();
                        //updating belt of user
                        $belts = Battles::getBeltCount($battle->user_id);
                        if ($belts > 0) {
                            $achievementType = AchievementTypes::select('id')->where('achievement_id', $achievement->id)->first();

                            if ($achievementType->id) {
                                $beltsData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $battle->user_id)
                                        ->where('achievement_id', $achievement->id)
                                        ->first();
                                if ($beltsData) {
                                    if ($beltsData->metric_value < $belts) {
                                        $beltsData->metric_value = $belts;
                                        $beltsData->count = $belts;
                                        $beltsData->shared = false;
                                        $beltsData->session_id = $sessionId;
                                        $beltsData->awarded = true;
                                        $beltsData->updated_at = \Carbon\Carbon::now();
                                        $beltsData->save();
                                        $newArchievements[] = $beltsData->id;
                                    }
                                } else {
                                    $userAchievements = UserAchievements::Create(['user_id' => $battle->user_id,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $belts,
                                                'count' => $belts,
                                                'awarded' => true,
                                                'session_id' => $sessionId]);
                                    $newArchievements[] = $userAchievements->id;
                                }
                            }
                        }
                        //updating belt of opponent
                        $belts = Battles::getBeltCount($battle->opponent_user_id);
                        if ($belts > 0) {
                            $achievementType = AchievementTypes::select('id')->where('achievement_id', $achievement->id)->first();

                            if ($achievementType->id) {
                                $beltsData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $battle->opponent_user_id)
                                        ->where('achievement_id', $achievement->id)
                                        ->first();
                                if ($beltsData) {
                                    if ($beltsData->metric_value < $belts) {
                                        $beltsData->metric_value = $belts;
                                        $beltsData->count = $belts;
                                        $beltsData->shared = false;
                                        $beltsData->session_id = $sessionId;
                                        $beltsData->awarded = true;
                                        $beltsData->updated_at = \Carbon\Carbon::now();
                                        $beltsData->save();
                                        $newArchievements[] = $beltsData->id;
                                    }
                                } else {
                                    $userAchievements = UserAchievements::Create(['user_id' => $battle->opponent_user_id,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $belts,
                                                'count' => $belts,
                                                'awarded' => true,
                                                'session_id' => $sessionId]);
                                    $newArchievements[] = $userAchievements->id;
                                }
                            }
                        }
                    }
                    break;
                case 2:
                    \Log::info('in 2');
                    $punchCount = Sessions::getPunchCount();
                    if ($punchCount > 0) {
                        $achievementTypes = AchievementTypes::select(\DB::raw('MAX(config) as max_val'), 'id')->where('config', '<=', $punchCount)
                                        ->where('achievement_id', $achievement->id)->groupBy('id')->get();
                        
                        foreach($achievementTypes as $achievementType){
                            if ($achievementType->id) {
                                $getUserPunchData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $userId)
                                        ->where('achievement_id', $achievement->id)
                                        // ->where('metric_value', $achievementType->max_val)
                                        ->orderBy('updated_at', 'desc')
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
                                    $newArchievements[] = $userAchievements->id;
                                } else {
                                    if ($timeThisMonday > $getUserPunchData->updated_at) {
                                        $getUserPunchData->count ++;
                                        $getUserPunchData->metric_value = $achievementType->max_val;
                                        $getUserPunchData->goal_id = $goalId;
                                        $getUserPunchData->session_id = $sessionId;
                                        $getUserPunchData->shared = false;
                                        $getUserPunchData->updated_at = \Carbon\Carbon::now();
                                        $getUserPunchData->save();
                                        $newArchievements[] = $getUserPunchData->id;
                                    }
                                }
                            }
                        }
                    }
                    break;
                case 3:
                    \Log::info('in 3');
                    $averPunchesPerMin = 0;
                    if (empty($battleId)) {
                        $averPunchesPerMin = SessionRounds::getMostPunchesPerMinute($sessionId);
                        if ($averPunchesPerMin > 0) {
                            $achievementTypes = AchievementTypes::select(\DB::raw('MAX(config) as max_val'), 'id')->where('config', '<=', $averPunchesPerMin)
                                            ->where('achievement_id', $achievement->id)->groupBy('id')->get();
                            foreach($achievementTypes as $achievementType) {
                                if ($achievementType->id) {
                                    $mostPunchesData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                            ->where('user_id', $userId)
                                            ->where('achievement_id', $achievement->id)
                                            ->where('metric_value', $achievementType->max_val)
                                            ->orderBy('updated_at', 'desc')
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
                                        $newArchievements[] = $userAchievements->id;
                                    } else {
                                        if ($timeThisMonday > $mostPunchesData->updated_at) {
                                            $mostPunchesData->count ++;
                                            $mostPunchesData->metric_value = $achievementType->max_val;
                                            $mostPunchesData->goal_id = $goalId;
                                            $mostPunchesData->session_id = $sessionId;
                                            $mostPunchesData->shared = false;
                                            $mostPunchesData->updated_at = \Carbon\Carbon::now();
                                            $mostPunchesData->save();
                                            $newArchievements[] = $mostPunchesData->id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;
                case 4:
                    \Log::info('in 4');
                    $goal = Goals::getAccomplishedGoal();
                    if ($goal == 1) {
                        $achievementType = AchievementTypes::select('id')->where('achievement_id', $achievement->id)->first();

                        $goalData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                ->where('user_id', $userId)
                                ->where('achievement_id', $achievement->id)
                                ->orderBy('updated_at', 'desc')
                                ->first();
                        if ($goalData) {
                            $goalMatrix = $goalData->metric_value + 1;
                            $goalData->metric_value = $goalMatrix;
                            $goalData->session_id = $sessionId;
                            $goalData->count = $goalMatrix;
                            $goalData->shared = false;
                            $goalData->awarded = true;
                            $goalData->updated_at = \Carbon\Carbon::now();
                            $goalData->save();
                            $newArchievements[] = $goalData->id;
                        } else {
                            $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $achievementType->id,
                                        'metric_value' => $goal,
                                        'awarded' => true,
                                        'count' => $goal,
                                        'session_id' => $sessionId]);
                            $newArchievements[] = $userAchievements->id;
                        }
                    }
                    break;
                case 5:
                case 6:
                    //\Log::info('in 6');
                    $speedAndPunch = $mostPowefulSpeed;
                    if ($achievement->id == 5) {
                        $speedAndPunch = $mostPowefulPunch;
                    }
                    $achievementType = AchievementTypes::select('min', 'id')->where('achievement_id', $achievement->id)->first();
                    if ($speedAndPunch > $achievementType->min) {
                        $mostPowefulSpeedData = UserAchievements::where('achievement_type_id', $achievementType->id)
                                ->where('user_id', $userId)
                                // ->where('goal_id', $goalId)
                                ->where('achievement_id', $achievement->id)
                                ->orderBy('updated_at', 'desc')
                                ->first();
                        if ($mostPowefulSpeedData) {
                            $isSave = false;
                            if ($mostPowefulSpeedData->metric_value < $speedAndPunch) {
                                $mostPowefulSpeedData->metric_value = $speedAndPunch;
                                $isSave = true;
                            }
                            if ($timeThisMonday > $mostPowefulSpeedData->updated_at) {
                                $mostPowefulSpeedData->count ++;
                                $isSave = true;
                            }
                            if ($isSave) {
                                $mostPowefulSpeedData->goal_Id = $goalId;
                                $mostPowefulSpeedData->session_id = $sessionId;
                                $mostPowefulSpeedData->shared = false;
                                $mostPowefulSpeedData->awarded = true;
                                $mostPowefulSpeedData->updated_at = \Carbon\Carbon::now();
                                $mostPowefulSpeedData->Save();
                                $newArchievements[] = $mostPowefulSpeedData->id;
                            }
                        } else {
                            $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                        'count' => 1,
                                        'awarded' => true,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $achievementType->id,
                                        'metric_value' => $speedAndPunch,
                                        'goal_id' => $goalId,
                                        'session_id' => $sessionId]);
                            $newArchievements[] = $userAchievements->id;
                        }
                    }
                    break;
                case 7:
                    \Log::info('in 7');
                    $userParticpation = Sessions::getUserParticpation($userId, $perviousMonday);
                    if ($userParticpation) {
                        $achievementTypes = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<=', $userParticpation)
                                //->where('max', '>=', $userParticpation)
                                ->get();
                        foreach($achievementTypes as $achievementType) {
                            if ($achievementType->id) {
                                $userParticpationData = UserAchievements::where('achievement_id', $achievement->id)
                                        ->where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $userId)
                                        ->orderBy('updated_at', 'desc')
                                        ->first();
                                
                                if (empty($userParticpationData)) {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $userParticpation,
                                                'count' => 1,
                                                'awarded' => true,
                                                'session_id' => $sessionId
                                    ]);
                                    $newArchievements[] = $userAchievements->id;
                                } else {
                                    if ($timeThisMonday > $userParticpationData->updated_at) {
                                        $userParticpationData->count ++;
                                        $userParticpationData->metric_value = $userParticpation;
                                        $userParticpationData->session_id = $sessionId;
                                        $userParticpationData->shared = false;
                                        $userParticpationData->updated_at = \Carbon\Carbon::now();
                                        $userParticpationData->save();
                                        $newArchievements[] = $userParticpationData->id;
                                    }
                                }
                            }
                        }
                    }
                    break;
                case 9:
                    \Log::info('in 9');
                    $accuracy = Sessions::getAccuracy($userId, $perviousMonday);
                    if ($accuracy) {
                        $achievementTypes = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<=', $accuracy)
                                //->where('max', '>=', $accuracy)
                                ->get();
                        foreach($achievementTypes as $achievementType){
                            if ($achievementType) {
                                $accuracyData = UserAchievements::where('achievement_id', $achievement->id)
                                        ->where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $userId)
                                        ->orderBy('updated_at', 'desc')
                                        ->first();
                                
                                if (empty($accuracyData)) {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $accuracy,
                                                'count' => 1,
                                                'awarded' => true,
                                                'session_id' => $sessionId
                                    ]);
                                    $newArchievements[] = $userAchievements->id;
                                } else {
                                    if ($timeThisMonday > $accuracyData->updated_at) {
                                        $accuracyData->count ++;
                                        $accuracyData->metric_value = $accuracy;
                                        $accuracyData->session_id = $sessionId;
                                        $accuracyData->shared = false;
                                        $accuracyData->updated_at = \Carbon\Carbon::now();
                                        $accuracyData->save();
                                        $newArchievements[] = $accuracyData->id;
                                    }
                                }
                            }
                        }
                    }
                    break;
                case 10:
                    \Log::info('in 10');
                    $config = $achievement->male;
                    if ($gender == 'female') {
                        $config = $achievement->female;
                    };
                    $strongMan = Sessions::getStrongMen($config, $userId, $perviousMonday);
                    if ($strongMan) {
                        $achievementTypes = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<=', $strongMan)
                                //->where('max', '>=', $strongMan)
                                ->get();
                        foreach($achievementTypes as $achievementType){
                            if ($achievementType) {
                                $strongManData = UserAchievements::where('achievement_id', $achievement->id)
                                        ->where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $userId)
                                        ->orderBy('updated_at', 'desc')
                                        ->first();
                                
                                if (empty($strongManData)) {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $strongMan,
                                                'count' => 1,
                                                'awarded' => true,
                                                'session_id' => $sessionId
                                    ]);
                                    $newArchievements[] = $userAchievements->id;
                                } else {
                                    if ($timeThisMonday > $strongManData->updated_at) {
                                        $strongManData->count ++;
                                        $strongManData->metric_value = $strongMan;
                                        $strongManData->shared = false;
                                        $strongManData->session_id = $sessionId;
                                        $strongManData->updated_at = \Carbon\Carbon::now();
                                        $strongManData->Save();
                                        $newArchievements[] = $strongManData->id;
                                    }
                                }
                            }
                        }
                    }
                    break;
                case 11:
                    \Log::info('in 11');
                    $config = $achievement->male;
                    if ($gender == 'female') {
                        $config = $achievement->female;
                    }
                    
                    $speedDemon = Sessions::getSpeedDemon($config, $userId, $perviousMonday);

                    if ($speedDemon) {
                        $achievementTypes = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('min', '<=', $speedDemon)
                                //->where('max', '>=', $speedDemon)
                                ->get();
                        foreach($achievementTypes as $achievementType){                                
                            
                            if ($achievementType) {
                                $speedDemonData = UserAchievements::where('achievement_id', $achievement->id)
                                        ->where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $userId)
                                        ->orderBy('updated_at', 'desc')
                                        ->first();
                                
                                if (empty($speedDemonData)) {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                                'achievement_id' => $achievement->id,
                                                'achievement_type_id' => $achievementType->id,
                                                'metric_value' => $speedDemon,
                                                'count' => 1,
                                                'awarded' => true,
                                                'session_id' => $sessionId
                                    ]);
                                    $newArchievements[] = $userAchievements->id;
                                } else if ($timeThisMonday > $speedDemonData->updated_at) {
                                    $speedDemonData->count ++;
                                    $speedDemonData->metric_value = $speedDemon;
                                    $speedDemonData->session_id = $sessionId;
                                    $speedDemonData->shared = false;
                                    $speedDemonData->updated_at = \Carbon\Carbon::now();
                                    $speedDemonData->Save();
                                    $newArchievements[] = $speedDemonData->id;
                                }
                            }
                        }
                    }
                    break;
                case 12:
                    \Log::info('in 12');
                    $ironFirst = Sessions::ironFirst($userId, $perviousMonday);

                    if ($ironFirst) {
                        $achievementTypes = AchievementTypes::select('id')
                                ->where('achievement_id', $achievement->id)
                                ->where('gender', $gender)
                                ->where('min', '<=', $ironFirst)
                                //->where('max', '>=', $ironFirst)
                                ->get();
                      
                        foreach($achievementTypes as $achievementType) {
                            if ($achievementType->id) {
                                $ironFirstData = UserAchievements::where('achievement_id', $achievement->id)
                                        ->where('achievement_type_id', $achievementType->id)
                                        ->where('user_id', $userId)
                                        ->orderBy('updated_at', 'desc')
                                        ->first();
                                if (empty($ironFirstData)) {
                                    $userAchievements = UserAchievements::Create(['user_id' => $userId,
                                        'achievement_id' => $achievement->id,
                                        'achievement_type_id' => $achievementType->id,
                                        'metric_value' => $ironFirst,
                                        'count' => 1,
                                        'awarded' => true,
                                        'session_id' => $sessionId
                                    ]);
                                    $newArchievements[] = $userAchievements->id;
                                } else if ($timeThisMonday > $ironFirstData->updated_at) {
                                    $ironFirstData->count ++;
                                    $ironFirstData->metric_value = $ironFirst;
                                    $ironFirstData->session_id = $sessionId;
                                    $ironFirstData->shared = false;
                                    $ironFirstData->updated_at = \Carbon\Carbon::now();
                                    $ironFirstData->Save();
                                    $newArchievements[] = $ironFirstData->id;
                                }
                            }
                        }
                    }
                    break;
            }
        }
        //\Log::info('USER ID>>>'.$userId);
        //\Log::info('SESSION ID>>>'.$sessionId);
        return UserAchievements::getSessionAchievements($userId, $sessionId, $newArchievements);
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
        } else {
            $battle->opponent_finished = 1;
            $battle->opponent_finished_at = date('Y-m-d H:i:s');

            $pushToUserId = $battle->user_id;
            $pushOpponentUserId = $battle->opponent_user_id;
        }
        $battle->update();
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
        $goal = Goals::where('user_id', \Auth::user()->id)->where('followed', 1)
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

                $goal->done_count = $session->punches_count + $goal->done_count;
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
