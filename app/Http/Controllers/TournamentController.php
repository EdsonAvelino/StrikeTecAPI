<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;
use App\EventUser;
use App\FanActivity;
use App\EventFanActivity;
use App\EventSession;
use Validator;
use DB;

class TournamentController extends Controller
{

    /**
     * @api {get} /tournaments/all Get all tournaments
     * @apiGroup Tournaments
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Get all tournaments
     * @apiSuccessExample {json} Success
     * {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *                "id": 5,
     *                "company_name": "Rebook",
     *                "event_title": "q",
     *                "location": "San Francisco ",
     *                "description": "q",
     *                "image": null,
     *                "to_date": "2017-11-30",
     *                "to_time": "23:42:00",
     *                "from_date": "2017-11-30",
     *                "from_time": "23:42:00",
     *                "all_day": false,
     *                "status": true,
     *                "users_count": 2
     *            },
     *            {
     *                "id": 3,
     *                "company_name": "Rebook",
     *                "event_title": "yrtfg",
     *                "location": "Las Vegas, Nevada",
     *                "description": "qwdxz",
     *                "image": "http://striketec.dev/storage/fanuser/event/ac-1515508592.jpeg",
     *                "to_date": "2017-11-12",
     *                "to_time": "20:30:00",
     *                "from_date": "2018-12-12",
     *                "from_time": "20:00:00",
     *                "all_day": false,
     *                "status": true,
     *                "users_count": 2
     *            }
     *       ]
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getTournamentList(request $request)
    {
        try {
            $userId = \Auth::user()->id;
            $offset = (int) ($request->get('start') ? $request->get('start') : 0);
            $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

            $eventList = Event::select('events.id', \DB::raw('company_id as company_name'), 'event_title', \DB::raw('location_id as location'), 'description', \DB::raw('image as image'), 'to_date', 'to_time', 'from_date', 'from_time', 'all_day', 'events.status', \DB::raw('events.id as users_count'))
                    ->whereHas('eventUser', function($q) use($userId) {
                        $q->where('user_id', '!=', $userId);
                    })->where('events.status', 1)
                    ->orderBy('from_date', 'desc')
                    ->offset($offset)->limit($limit)
                    ->get();

            return response()->json(['error' => 'false', 'message' => '', 'data' => $eventList]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {get} /tournaments/user Get user's tournaments
     * @apiGroup Tournaments
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Get list of user's tournaments
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "",
     *       "data": [
     *          {
     *                "id": 5,
     *                "company_name": "Rebook",
     *                "event_title": "q",
     *                "location": "San Francisco ",
     *                "description": "q",
     *                "image": null,
     *                "to_date": "2017-11-30",
     *                "to_time": "23:42:00",
     *                "from_date": "2017-11-30",
     *                "from_time": "23:42:00",
     *                "all_day": false,
     *                "status": true,
     *                "users_count": 2
     *            },
     *            {
     *                "id": 3,
     *                "company_name": "Rebook",
     *                "event_title": "yrtfg",
     *                "location": "Las Vegas, Nevada",
     *                "description": "qwdxz",
     *                "image": "http://striketec.dev/storage/fanuser/event/ac-1515508592.jpeg",
     *                "to_date": "2017-11-12",
     *                "to_time": "20:30:00",
     *                "from_date": "2018-12-12",
     *                "from_time": "20:00:00",
     *                "all_day": false,
     *                "status": true,
     *                "users_count": 2
     *            }
     *       ]
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getUserTournaments(request $request)
    {
        try {
            $userId = \Auth::user()->id;
            $offset = (int) ($request->get('start') ? $request->get('start') : 0);
            $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
            $eventList = Event::select('events.id', \DB::raw('company_id as company_name'), 'event_title', \DB::raw('location_id as location'), 'description', \DB::raw('image as image'), 'to_date', 'to_time', 'from_date', 'from_time', 'all_day', 'events.status', \DB::raw('events.id as users_count'))
                    ->whereHas('eventUser', function($q) use($userId) {
                        $q->where('user_id', '=', $userId);
                    })->where('events.status', 1)
                    ->orderBy('from_date', 'desc')
                    ->offset($offset)->limit($limit)
                    ->get();
            return response()->json(['error' => 'false', 'message' => '', 'data' => $eventList]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {post} /tournaments/register register user to tournament
     * @apiGroup Tournaments
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id id of tournament
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message":"Registration Successfully done.!!"
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
    public function registerUser(request $request)
    {
        try {
            $userId = \Auth::user()->id;
            $eventId = $request->get('event_id');
            EventUser::firstOrCreate(['event_id' => $eventId, 'user_id' => $userId], [ 'status' => 0, 'register_via' => 'm']);
            return response()->json([
                        'error' => 'false',
                        'message' => 'Registration Successfully done.!!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {get} /tournament get tournament details
     * @apiGroup Tournaments
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id id of tournament
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data All events list information
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "",
     *       "data": {
     *                "id": 1,
     *                "company_name": "Rebook",
     *                "event_title": "yrtfg",
     *                "location": "Las Vegas, Nevada",
     *                "description": "qwdxz",
     *                "image": "http://striketec.dev/storage/fanuser/event/ac-1515508592.jpeg",
     *                "to_date": "2017-11-12",
     *                "to_time": "20:30:00",
     *                "from_date": "2018-12-12",
     *                "from_time": "20:00:00",
     *                "all_day": false,
     *                "status": true,
     *                "users_count": 2
     *            }
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getEventDetail(request $request)
    {
        try {
            $userId = \Auth::user()->id;
            $eventID = $request->get('event_id');
            $event = Event::select('id', \DB::raw('company_id as company_name'), 'event_title', \DB::raw('location_id as location'), 'description', 'image', 'to_date', 'to_time', 'from_date', 'from_time', 'all_day', 'status', \DB::raw('id as users_count'))
                            ->where('id', $eventID)
                            ->get()->first();
            return response()->json([
                        'error' => 'false',
                        'message' => '',
                        'data' => $event
            ]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

}
