<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\EventSession;
use App\EventSessionPunches;
use Validator;
use DB;

class EventTrainingController extends Controller
{

    /**
     * @api {post} /fan/event/training/sessions upload event session and punches
     * @apiGroup Event
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
     *      {
     *    "participant_data": {
     *      "activity_id": 2,
     *      "activity_time": 0,
     *      "end_time": 0,
     *      "event_id": 68,
     *      "gloves_weight": 0,
     *      "participant_id": 109,
     *      "prepare_time": "30",
     *      "start_time": 1513955976946,
     *      "sync": 0,
     *      "warning_time": "30",
     *      "weight": 200,
     *      "rowId": 3
     *    },
     *    "participant_stats_data": {
     *      "avg_force": 404.4237288135593,
     *      "avg_speed": 21.305084745762713,
     *      "finished": 0.0,
     *      "best_time": 0.0,
     *      "max_force": 593.0,
     *      "max_speed": 34.0,
     *      "participant_fk": 0.0,
     *      "punches_count": 59,
     *      "sync": 0.0
     *    },
     *    "participant_punch_data": [
     *      {
     *        "force": 306,
     *        "hand": "R",
     *        "punch_duration": 0.5,
     *        "punch_time": "1513955976999",
     *        "punch_type": "U",
     *        "speed": 6,
     *        "sync": 0
     *      },
     *      {
     *        "force": 356,
     *        "hand": "L",
     *        "punch_duration": 0.5,
     *        "punch_time": "1513955977984",
     *        "punch_type": "H",
     *        "speed": 6,
     *        "sync": 0
     *      },
     *    ]
     *  }
     *  ]
     * }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains each sessions' start_time
     * @apiSuccessExample {json} Success
     * HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Event training sessions saved successfully",
     *   }
     * }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function storeEventSessions(Request $request)
    {  
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required',
        ]);
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('participant_id')]);
        }
        try{
            $participantData = $request->get('participant_data');
            $paricipantSessionData = $request->get('participant_stats_data');
            $paricipantPunchData = $request->get('participant_punch_data');

            /************************* Session create **********************************/
                $_session = EventSession::create([
                    'participant_id' => $participantData['participant_id'],
                    'event_id' => $participantData['event_id'],
                    'activity_id' => $participantData['activity_id'],
                    'start_time' => $participantData['start_time'],
                    'end_time' => ($participantData['end_time']) ? $participantData['end_time'] : '',
                    'plan_id' => !empty($participantData['plan_id']) ? $participantData['plan_id'] : '',
                    'avg_speed' => $paricipantSessionData['avg_speed'],
                    'avg_force' => $paricipantSessionData['avg_force'],
                    'punches_count' => $paricipantSessionData['punches_count'],
                    'max_force' => $paricipantSessionData['max_force'],
                    'max_speed' => $paricipantSessionData['max_speed'],
                    'best_time' => $paricipantSessionData['best_time']
                ]);
            /************************* punchase create **********************************/
            foreach ($paricipantPunchData as $val) {
                $_punch = EventSessionPunches::create([
                            'event_session_id' =>  $_session->id,
                            'punch_time' => $val['punch_time'],
                            'punch_duration' => $val['punch_duration'],
                            'force' => $val['force'],
                            'speed' => $val['speed'],
                            'punch_type' => strtoupper($val['punch_type']),
                            'hand' => strtoupper($val['hand']),
                ]);
            }
            return response()->json([
                        'error' => 'false',
                        'message' => 'Event training sessions saved successfully',
            ]);
        } catch(Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid request',
            ]);
        }
    }
    
    
    /**
     * @api {get} /fan/event/leaderboard Get leaderboard 
     * @apiGroup Event
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} session Sessions information
     * @apiSuccess {Object} 
     * @apiSuccessExample {json} Success 
     * {
     * "error": "false",
     * "message": "Leaderboard information",
     * "data": [
     *   {
     *       "event_id": 68,
     *       "activity_id": 3,
     *       "status": true,
     *        "event_sessions": [
     *       {
     *           "id": 3,
     *           "participant_id": 12,
     *           "event_id": 2,
     *           "activity_id": 3,
     *           "start_time": 1513955976946,
     *           "end_time": 0,
     *           "plan_id": 0,
     *           "avg_speed": 21.305084745763,
     *           "avg_force": 404.42372881356,
     *           "punches_count": 59,
     *           "max_speed": 137,
     *           "max_force": 593,
     *           "best_time": "0",
     *           "created_at": "2017-12-22 15:19:36",
     *           "updated_at": "2017-12-26 20:11:01",
     *           "user": {
     *               "id": 12,
     *               "first_name": "Anchal",
     *               "last_name": "Gupta",
     *               "name": "Anchal Gupta",
     *               "photo_url": null
     *           }
     *       },
     *       {
     *           "id": 2,
     *           "participant_id": 7,
     *           "event_id": 2,
     *           "activity_id": 3,
     *           "start_time": 1513955976946,
     *           "end_time": 0,
     *           "plan_id": 0,
     *           "avg_speed": 21.305084745763,
     *           "avg_force": 404.42372881356,
     *           "punches_count": 59,
     *           "max_speed": 38,
     *           "max_force": 593,
     *           "best_time": "0",
     *           "created_at": "2017-12-22 15:19:36",
     *           "updated_at": "2017-12-26 20:10:59",
     *           "user": {
     *               "id": 7,
     *               "first_name": "Qiang",
     *               "last_name": "Hu",
     *               "name": "Qiang Hu",
     *               "photo_url": "http://172.16.11.45/storage/profileImages/sub-1509460359.png"
     *           }
     *       }
     *   ]
     *     }
     *   ]
     *  }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getLeaderboardByEventActivity(Request $request)
    {   
        try{
            $eventID = $request->get('event_id');
            $activityID = $request->get('activity_id');
            $leaderBoardDetails = \App\EventFanActivity::select('event_id', 'activity_id')->with(['eventSessions.user', 'eventSessions' => function($q) use ($activityID) {
                if($activityID == 1) {
                    $q->where('activity_id', $activityID)->orderBy('max_speed', 'desc');
                }
                if($activityID == 2) {
                    $q->where('activity_id', $activityID)->orderBy('max_force', 'desc');
                }
                if($activityID == 3) {
                    $q->where('activity_id', $activityID)->orderBy('max_speed', 'desc');
                }
            }])
                    ->where('event_id', $eventID)
                    ->where('activity_id', $activityID)->first();
        if (!empty($leaderBoardDetails)) {
                return response()->json([
                            'error' => 'false',
                            'message' => 'Leaderboard information',
                            'data' => $leaderBoardDetails,
                ]);
            }
        } catch(Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid request',
            ]);
        }
    }
   
    /**
     * @api {delete} /fan/event/participant/remove remove participant
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} id id of event
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 1,
     *      "activity_id" : 2,
     *      "participant_id" : 3
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     * HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Participant has been removed successfully",
     *   }
     * }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function eventParticipantsRemove(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'participant_id' => 'required|exists:event_sessions',
        ]);
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('participant_id')]);
        }
        try {
            $eventID = $request->get('event_id');
            $activitytID = $request->get('activity_id');
            $participantID = $request->get('participant_id');
            DB::beginTransaction();
            $participentDetails = EventSession::where('event_id', $eventID)
                        ->where('activity_id', $activitytID)
                        ->where('participant_id', $participantID)->delete();
            DB::commit();
            if($participentDetails) {
                return response()->json([
                    'error' => 'false',
                    'message' => 'Participent has been removed successfully'
                ]);
            } 
            return response()->json([
                    'error' => 'false',
                    'message' => 'Participent not removed successfully please try again!'
                ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                    'error' => 'true',
                    'message' => 'Invalid request',
            ]);
        }
    }   
}
?> 