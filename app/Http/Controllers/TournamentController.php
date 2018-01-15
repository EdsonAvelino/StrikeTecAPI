<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events;
use App\EventActivities;
use App\EventParticipants;
use App\User;
use App\UserConnections;
use App\Helpers\Push;
use App\Helpers\PushTypes;

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
     *               "id": 4,
     *               "event_activity_type_id": 2,
     *               "event_title": "UFC FIGHT NIGHT JACARE VS BRUNSON 2",
     *               "description": null,
     *               "image": null,
     *               "user_joined": false,
     *               "activity_started": false,
     *               "activity_finished": false,
     *               "user_counts": 100,
     *               "user_done": true,
     *               "user_score": 31
     *           },
     *           {
     *               "id": 5,
     *               "event_activity_type_id": 3,
     *               "event_title": "UFC FIGHT NIGHT MACHIDA VAN ANDERS",
     *               "description": null,
     *               "image": null,
     *               "user_joined": false,
     *               "activity_started": false,
     *               "activity_finished": false,
     *               "user_counts": 200,
     *               "user_done": false,
     *               "user_score": 25
     *           },
     *           {
     *               "id": 6,
     *               "event_activity_type_id": 2,
     *               "event_title": "UFC 223 CHRIS VS CHOI",
     *               "description": null,
     *               "image": null,
     *               "user_joined": false,
     *               "activity_started": false,
     *               "activity_finished": false,
     *               "user_counts": 150,
     *               "user_done": true,
     *               "user_score": 29
     *           },
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
            \DB::raw('id as user_done'),
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
            $_eventActivity['activity_finished'] = (bool) $eventActivity->status;
            $_eventActivity['user_counts'] = $eventActivity->user_counts;
            $_eventActivity['user_done'] = $eventActivity->user_done;
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
     * @apiParam {int} event_activity_id Dd of tournament activity
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

            $userNotification = \App\UserNotifications::where('notification_type_id', \App\UserNotifications::TOURNAMENT_ACTIVITY_INVITE)->where('user_id', \Auth::id())->where('data_id', $eventActivityId)->first();
            $userNotification->is_read = true;
            $userNotification->read_at = $userNotification->freshTimestamp();
            $userNotification->save();
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
     *              "id": 2,
     *              "event_activity_type_id": 1,
     *              "event_title": "UFC FIGHT NIGHT CERRONE VS MEDEIROS",
     *              "description": null,
     *              "image": null,
     *              "user_joined": true,
     *              "activity_started": false,
     *              "c": null,
     *              "user_counts": 100,
     *              "user_done": null,
     *              "user_score": 26
     *          },
     *          {
     *              "id": 3,
     *              "event_activity_type_id": 2,
     *              "event_title": "UFC FIGHT NIGHT CERRONE VS MEDEIROS",
     *              "description": null,
     *              "image": null,
     *              "user_joined": true,
     *              "activity_started": false,
     *              "activity_finished": true,
     *              "user_counts": 120,
     *              "user_done": false,
     *              "user_score": 39
     *          }
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
            \DB::raw('id as user_done')
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
            $_eventActivity['activity_finished'] = (bool) $eventActivity->status;
            $_eventActivity['user_counts'] = $eventActivity->user_counts;
            $_eventActivity['user_done'] = $eventActivity->user_done;
            $_eventActivity['user_score'] = $eventActivity->user_score;

            $eventsList[] = $_eventActivity;
        }
        
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
     *              "id": 5,
     *              "event_activity_type_id": 1,
     *              "event_title": "UFC FIGHT NIGHT CERRONE VS MEDEIROS",
     *              "description": null,
     *              "image": null,
     *              "user_joined": true,
     *              "activity_started": true,
     *              "activity_finished": false,
     *              "user_counts": 100,
     *              "user_done": true,
     *              "user_score": 27
     *          },
     *          {
     *              "id": 6,
     *              "event_activity_type_id": 2,
     *              "event_title": "UFC FIGHT NIGHT CERRONE VS MEDEIROS",
     *              "description": null,
     *              "image": null,
     *              "user_joined": true,
     *              "activity_started": true,
     *              "activity_finished": ture,
     *              "user_counts": 120,
     *              "user_done": true,
     *              "user_score": 36
     *          }
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
            \DB::raw('id as user_done')
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
            $_eventActivity['activity_finished'] = (bool) $eventActivity->status;
            $_eventActivity['user_counts'] = $eventActivity->user_counts;
            $_eventActivity['user_done'] = $eventActivity->user_done;
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
     * @apiParam {Number} event_activity_id Event's Id
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "event_activity_id": 7,
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
     *              "user_follower": true,
     *              "photo_url": "http://example.com/image.jpg",
     *              "points": 8
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "points": 135,
     *              "user_following": true,
     *              "user_follower": true,
     *              "photo_url": "http://example.com/image.jpg",
     *              "points": 9
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "points": 140,
     *              "user_following": true,
     *              "user_follower": true,
     *              "photo_url": "http://example.com/image.jpg",
     *              "points": 9
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

        $eventParticipants = EventParticipants::select('user_id')->where('event_activity_id', $eventActivityID);

        $_connections = UserConnections::select('user_id')->where('follow_user_id', $userId)
                        ->whereRaw("user_id IN ($userFollowing)", [$userId])
                        ->whereNotIn('user_id', $eventParticipants)
                        ->offset($offset)->limit($limit)->get();;

        foreach ($_connections as $connection) {
            $user = User::get($connection->user_id);
            $joinedEventActivity = EventParticipants::where('event_activity_id', $eventActivityID)
                            ->where('user_id', $connection->user_id)->exists();

            // $user['joined'] = (bool) $joinedEventActivity;

            $connections[] = $user;
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $connections
        ]);
    }

    /**
     * @api {post} /user/tournaments/invite Invite connection for tournament activity
     * @apiGroup Tournaments
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} event_activity_id Event's ID
     * @apiParam {Number} user_id User connection ID
     * @apiParamExample {json} Input
     *    {
     *      "event_activity_id": 7,
     *      "user_id": 7
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Invitation sent",
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function getUserTournamentInvite(Request $request)
    {
        $userId = \Auth::user()->id;
        
        $eventActivityId = (int) $request->get('event_activity_id');
        $opponentUserId = (int) $request->get('user_id');

        // Send Push Notification
        $pushMessage = \Auth::user()->first_name . ' ' . \Auth::user()->last_name . ' has invited you to event activity';

        Push::send(PushTypes::TOURNAMENT_ACTIVITY_INVITE, $opponentUserId, \Auth::user()->id, $pushMessage, ['event_activity_id' => $eventActivityId]);

        // Generates new notification for user
        \App\UserNotifications::generate(\App\UserNotifications::TOURNAMENT_ACTIVITY_INVITE, $opponentUserId, \Auth::id(), $eventActivityId);

        return response()->json([
            'error' => 'false',
            'message' => 'Invitation sent'
        ]);
    }
}