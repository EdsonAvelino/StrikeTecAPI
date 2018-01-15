<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events;
use App\EventActivities;
use App\EventParticipants;
use App\User;
use App\UserConnections;

class TournamentController extends Controller
{
    /**
     * @api {get} /tournaments Get tournaments user did have not joined
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
     *                "event_type": 2,
     *                "company_name": "Rebook",
     *                "event_title": "q",
     *                "location": "San Francisco ",
     *                "description": "q",
     *                "image": null,
     *                "start_date": 1514532000,
     *                "end_date": 1514532000,
     *                "user_score": 0,
     *                "status": true,
     *                "user_registered": false,
     *                "joined": false,
     *                "event_started": false,
     *                "users_count": 10
     *            },
     *            {
     *                "id": 6,
     *                "event_type": 2,
     *                "company_name": "Monster",
     *                "event_title": "Monster Energy",
     *                "location": "San Francisco ",
     *                "description": "q",
     *                "image": null,
     *                "start_date": 1514532000,
     *                "end_date": 1514532000,
     *                "user_score": 0,
     *                "status": true,
     *                "user_registered": false,
     *                "joined": false,
     *                "event_started": false,
     *                "users_count": 11
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
    public function getEventsList(Request $request)
    {
        $userId = \Auth::user()->id;
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $alreadyJoined = EventParticipants::select('event_activity_id')->where('user_id', \Auth::id());

        $eventActivities = EventActivities::with(['event' => function($query) {
            $query->where('end_date', '>=', date('Y-m-d'));
        }])->select([
            '*',
            \DB::raw('id as user_joined'),
            \DB::raw('id as activity_started'),
            \DB::raw('id as user_counts'),
            \DB::raw('id as user_score'),
        ])->whereNotIn('id', $alreadyJoined)->where('status', 1)->offset($offset)->limit($limit)->get();

        $eventsList = [];

        foreach ($eventActivities as $eventActivity) {
            $_eventActivity = [];
            $_eventActivity['id'] = $eventActivity->id;
            $_eventActivity['event_activity_type_id'] = $eventActivity->event_activity_type_id;
            $_eventActivity['event_title'] = $eventActivity->event->title;
            $_eventActivity['description'] = $eventActivity->event->description;
            $_eventActivity['image'] = $eventActivity->event->image;
            $_eventActivity['user_joined'] = $eventActivity->user_joined;
            $_eventActivity['activity_started'] = $eventActivity->activity_started;
            $_eventActivity['activity_finished'] = null;
            $_eventActivity['user_counts'] = $eventActivity->user_counts;
            $_eventActivity['user_done'] = null;
            $_eventActivity['user_score'] = $eventActivity->user_score;

            $eventsList[] = $_eventActivity;
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $eventsList]);
    }

    /**
     * @api {post} /user/tournaments/join Register user to the tournament
     * @apiGroup Tournaments
     * @apiHeader {String} Authorization Authorization value
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *       "Content-Type": "application/x-www-form-urlencoded",
     *     }
     * @apiParam {int} event_id id of tournament
     * @apiParamExample {json} Input
     *    {
     *      "event_activity_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message":"Registration completed"
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
    public function userJoinTournament(Request $request)
    {
        $eventActivityId = $request->get('event_activity_id');

        $joined = (bool) EventParticipants::where('user_id', \Auth::user()->id)->where('event_activity_id', $eventActivityId)->exists();

        if (!$joined) {
            EventParticipants::Create([
                'event_activity_id' => $eventActivityId,
                'user_id' => \Auth::id(),
                'is_finished' => null,
                'joined_via' => 'M'
            ]);
        }

        return response()->json([
            'error' => 'false',
            'message' => 'Registration completed'
        ]);
    }

    /**
     * @api {get} /user/tournaments Get user's joined tournaments
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
     * @apiSuccess {Object} data Get list of user's joined tournaments
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "",
     *       "data": [
     *          {
     *                "id": 6,
     *                "event_type": 2,
     *                "company_name": "Monster",
     *                "event_title": "Monster Energy",
     *                "location": "San Francisco ",
     *                "description": "q",
     *                "image": null,
     *                "start_date": 1514532000,
     *                "end_date": 1514532000,
     *                "user_score": 0,
     *                "status": true,
     *                "user_registered": false,
     *                "joined": false,
     *                "event_started": false,
     *                "users_count": 11
     *            },
     *            {
     *                "id": 7,
     *                "event_type": 2,
     *                "company_name": "Monster",
     *                "event_title": "Monster Energy",
     *                "location": "San Francisco ",
     *                "description": "q",
     *                "image": null,
     *                "start_date": 1514532000,
     *                "end_date": 1514532000,
     *                "user_score": 0,
     *                "status": true,
     *                "user_registered": false,
     *                "joined": false,
     *                "event_started": false,
     *                "users_count": 11
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
    public function getUserJoinedTournaments(Request $request)
    {       
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $eventActivities = EventActivities::with('event')->select([
            '*',
            \DB::raw('id as user_joined'),
            \DB::raw('id as activity_started'),
            \DB::raw('id as user_counts'),
            \DB::raw('id as user_score'),
        ])->where('status', 1)
        ->whereHas('participant', function ($query) {
            $query->where('user_id', \Auth::id())->where(function($q) {
                $q->whereNull('is_finished')->orWhere('is_finished', 0);
            });
        })->offset($offset)->limit($limit)->get();

        $eventsList = [];

        foreach ($eventActivities as $eventActivity) {
            $_eventActivity = [];
            $_eventActivity['id'] = $eventActivity->id;
            $_eventActivity['event_activity_type_id'] = $eventActivity->event_activity_type_id;
            $_eventActivity['event_title'] = $eventActivity->event->title;
            $_eventActivity['description'] = $eventActivity->event->description;
            $_eventActivity['image'] = $eventActivity->event->image;
            $_eventActivity['user_joined'] = $eventActivity->user_joined;
            $_eventActivity['activity_started'] = $eventActivity->activity_started;
            $_eventActivity['activity_finished'] = null;
            $_eventActivity['user_counts'] = $eventActivity->user_counts;
            $_eventActivity['user_done'] = null;
            $_eventActivity['user_score'] = $eventActivity->user_score;

            $eventsList[] = $_eventActivity;
        }

        // $eventList = Event::select(
        //     'events.id',
        //     \DB::raw('id as event_type'),
        //     \DB::raw('company_id as company_name'),
        //     'event_title',
        //     \DB::raw('location_id as location'),
        //     'description',
        //     \DB::raw('image as image'),
        //     \DB::raw('CAST(UNIX_TIMESTAMP(CONCAT(from_date," ",from_time)) AS UNSIGNED) as start_date'),
        //     \DB::raw('CAST(UNIX_TIMESTAMP(CONCAT(to_date," ",to_time)) AS UNSIGNED) as end_date'),
        //     \DB::raw('id as user_score'),
        //     'events.status',
        //     \DB::raw('id as user_registered'),
        //     \DB::raw('id as joined'),
        //     \DB::raw('id as event_started'),
        //     \DB::raw('events.id as users_count')
        // )->whereHas('eventActivity', function($q) {
        //             $q->where('status', 0);
        //         })->whereHas('eventUser', function($q) use($userId) {
        //             $q->where('user_id', $userId)->where('status', 1)->where('user_finished', 0);
        //         })->where('events.status', 1)
        //         ->orderBy('from_date', 'desc')
        //         ->offset($offset)->limit($limit)
        //         ->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $eventsList]);
    }

    /**
     * @api {get} /user/tournaments/finished Get all finished tournaments that user joined
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
     * @apiSuccess {Object} data Get list of user's finished tournaments
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "",
     *       "data": [
     *          {
     *                "id": 6,
     *                "event_type": 2,
     *                "company_name": "Monster",
     *                "event_title": "Monster Energy",
     *                "location": "San Francisco ",
     *                "description": "q",
     *                "image": null,
     *                "start_date": 1514532000,
     *                "end_date": 1514532000,
     *                "user_score": 0,
     *                "status": true,
     *                "user_registered": false,
     *                "joined": false,
     *                "event_started": false,
     *                "users_count": 11
     *            },
     *            {
     *                "id": 7,
     *                "event_type": 2,
     *                "company_name": "Monster1",
     *                "event_title": "Monster Energy1",
     *                "location": "San Francisco ",
     *                "description": "q",
     *                "image": null,
     *                "start_date": 1514532000,
     *                "end_date": 1514532000,
     *                "user_score": 0,
     *                "status": true,
     *                "user_registered": false,
     *                "joined": false,
     *                "event_started": false,
     *                "users_count": 11
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
    public function getUserFinishedTournaments(Request $request)
    {
        $userId = \Auth::user()->id;

        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $eventActivities = EventActivities::with('event')->select([
            '*',
            \DB::raw('id as user_joined'),
            \DB::raw('id as activity_started'),
            \DB::raw('id as user_counts'),
            \DB::raw('id as user_score'),
        ])->where('status', 1)->whereHas('participant', function ($query) {
            $query->where('user_id', \Auth::id())->where('is_finished', 1);
        })->offset($offset)->limit($limit)->get();

        $eventsList = [];

        foreach ($eventActivities as $eventActivity) {
            $_eventActivity = [];
            $_eventActivity['id'] = $eventActivity->id;
            $_eventActivity['event_activity_type_id'] = $eventActivity->event_activity_type_id;
            $_eventActivity['event_title'] = $eventActivity->event->title;
            $_eventActivity['description'] = $eventActivity->event->description;
            $_eventActivity['image'] = $eventActivity->event->image;
            $_eventActivity['user_joined'] = $eventActivity->user_joined;
            $_eventActivity['activity_started'] = $eventActivity->activity_started;
            $_eventActivity['activity_finished'] = null;
            $_eventActivity['user_counts'] = $eventActivity->user_counts;
            $_eventActivity['user_done'] = null;
            $_eventActivity['user_score'] = $eventActivity->user_score;

            $eventsList[] = $_eventActivity;
        }
        
        return response()->json(['error' => 'false', 'message' => '', 'data' => $eventsList]);
    }

    /**
     * @api {get} /user/tournaments/<event_activity_id>/connections Get user's tournament connections who haven not joined yet
     * @apiGroup Tournaments
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} event_id Event's Id
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 7,
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains list of connections
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *          {
     *              "id": 5,
     *              "first_name": "Max",
     *              "last_name": "Zuck",
     *              "points": 125,
     *              "user_following": true,
     *              "user_follower": false,
     *              "photo_url": "http://example.com/image.jpg",
     *              "points": 9,
     *              "joined": true
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "points": 135,
     *              "user_following": true,
     *              "user_follower": true,
     *              "photo_url": "http://example.com/image.jpg",
     *              "points": 9,
     *              "joined": true
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "points": 140,
     *              "user_following": false,
     *              "user_follower": true,
     *              "photo_url": "http://example.com/image.jpg",
     *              "points": 9,
     *              "joined": true
     *          }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function getUserTournamentConnections(Request $request, $eventActivityID)
    {
        $userId = \Auth::user()->id;
        
        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $userFollowing = 'SELECT follow_user_id FROM user_connections WHERE user_id = ?';

        $connections = [];

        $_connections = UserConnections::select('user_id')->where('follow_user_id', $userId)
                        ->whereRaw("user_id IN ($userFollowing)", [$userId])
                        ->offset($offset)->limit($limit)->get();;

        foreach ($_connections as $connection) {
            $user = User::get($connection->user_id);
            $joinedEventActivity = EventParticipants::where('event_activity_id', $eventActivityID)
                            ->where('user_id', $connection->user_id)->exists();

            $user['joined'] = (bool) $joinedEventActivity;

            $connections[] = $user;
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $connections
        ]);
    }
}