<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Battles;
use App\Combos;
use App\ComboSets;
use App\Workouts;
use App\User;
use App\UserConnections;
use App\Leaderboard;

use App\Helpers\Push;
use App\PushTypes;

class BattleController extends Controller
{
    /**
     * @api {post} /battles Send battle invite
     * @apiGroup Battles
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} opponent_user_id Opponent UserId
     * @apiParam {Number} plan_id Selected combo-id, combo-set-id or workout-id
     * @apiParam {Number} type_id Type could be from { 3 = Combo, 4 = Combo-Set, 5=Workout }
     * @apiParamExample {json} Input
     *    {
     *      "opponent_user_id": 12,
     *      "plan_id": 1
     *      "type_id": 1
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "User invited for battle successfully",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function postBattleWithInvite(Request $request)
    {
        $opponentUserId = (int) $request->get('opponent_user_id');

        $battle = Battles::create([
                    'user_id' => \Auth::user()->id,
                    'opponent_user_id' => (int) $opponentUserId,
                    'plan_id' => (int) $request->get('plan_id'),
                    'type_id' => (int) $request->get('type_id')
        ]);

        $opponentUser = $battle->opponentUser;

        // Send Push Notification
        $pushMessage = \Auth::user()->first_name.' '.\Auth::user()->last_name.' has invited you for battle';
        $pushOpponentUser = User::select(['id', 'first_name', 'last_name', 'photo_url', \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points')])->where('id', \Auth::user()->id)->first();

        Push::send($opponentUserId, PushTypes::BATTLE_INVITE, $pushMessage, $pushOpponentUser);

        return response()->json([
            'error' => 'false',
            'message' => 'User invited for battle successfully',
            'data' => ['battle_id' => $battle->id, 'time' => strtotime($battle->created_at)]
        ]);
    }

    /**
     * @api {get} /battles/<battle_id> Get battle details
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} battle_id Selected battle's id to get details
     * @apiParamExample {json} Input
     *    {
     *      "battle_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Data will contain battle details
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": {
     *          "id": 8,
     *          "user_id": 31,
     *          "opponent_user_id": 1,
     *          "plan_id": 3,
     *          "type_id": 3,
     *          "accepted": null,
     *          "accepted_at": null,
     *          "user_finished": null,
     *          "opponent_finished": null,
     *          "user_finished_at": null,
     *          "opponent_finished_at": null,
     *          "winner_user_id": null,
     *          "created_at": "2017-10-30 19:01:53",
     *          "updated_at": "2017-10-30 19:01:53",
     *          "opponent_user": 
     *              {
     *                  "id": 1,
     *                  "first_name": "Nawaz",
     *                  "last_name": "Me",
     *                  "photo_url": null,
     *                  "points": 2768,
     *                  "user_following": true,
     *                  "user_follower": true
     *              },
     *          "sender_user_id": 1
     *      }
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getBattle($battleId)
    {
        $battleId = (int) $battleId;

        $_battle = Battles::find($battleId);

        /*
        $battleData = null;

        switch ($_battle->type_id) {
            case 3:
                $_combo = Combos::select('*', \DB::raw('id as key_set'))->where('id', $_battle->plan_id)->first()->toArray();

                $_combo['keys'] = explode('-', $_combo['key_set']);

                $battleData = $_combo;
                break;

            case 4:
                $comboSet = ComboSets::find($_battle->plan_id);

                $_comboSet = $comboSet->toArray();
                $_comboSet['combos'] = $comboSet->combos->pluck('combo_id')->toArray();

                $battleData = $_comboSet;
                break;

            case 5:
                $workout = Workouts::find($_battle->plan_id);
                $_workout = $workout->toArray();
                $combos = [];

                foreach ($workout->rounds as $round) {
                    $combos[] = $round->combos->pluck('combo_id')->toArray();
                }

                $_workout['combos'] = $combos;

                $battleData = $_workout;
                break;
        }
        */

        // Opponent user is opponent of current logged in user
        $opponentUserId = ($_battle->user_id == \Auth::user()->id) ? $_battle->opponent_user_id : $_battle->user_id;
        
        $opponentUser = User::select(['id', 'first_name', 'last_name', 'photo_url', \DB::raw('id as points'), \DB::raw('id as user_following'), \DB::raw('id as user_follower')])->where('id', $opponentUserId)->first();

        $battle = $_battle->toArray();

        $battle['opponent_user'] = $opponentUser->toArray();
        
        // ID of user who created the battle
        $battle['sender_user_id'] = $_battle->user_id;
        
        // Battle result
        $battle['battle_result'] = '';
        
        return response()->json(['error' => 'false', 'message' => '', 'data' => $battle]);
    }

    /**
     * @api {get} /battles/resend/<battle_id> Resend battle invite
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} battle_id Selected battle's id to resend invite
     * @apiParamExample {json} Input
     *    {
     *      "battle_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "User invited for battle successfully",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function resendBattleInvite($battleId)
    {
        $battleId = (int) $battleId;

        if (empty($battleId))
            return null;

        $battle = Battles::find($battleId);

        $user = $battle->user;
        $opponentUser = $battle->opponentUser;

        // Send Push Notification
        $pushMessage = $user->first_name.' '.$user->last_name.' has invited you for battle';
        $pushOpponentUser = User::select(['id', 'first_name', 'last_name', 'photo_url', \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points')])->where('id', \Auth::user()->id)->first();

        Push::send($battle->opponent_user_id, PushTypes::BATTLE_RESEND, $pushMessage, $pushOpponentUser);

        return response()->json([
            'error' => 'false',
            'message' => 'User invited for battle successfully',
            'data' => ['battle_id' => $battle->id, 'time' => strtotime($battle->created_at)]
        ]);
    }

    /**
     * @api {post} /battles/accept_decline Accept or Decline battle invite
     * @apiGroup Battles
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} battle_id Battle ID
     * @apiParam {Boolean} accept Either ture=accpted OR false=declined 
     * @apiParamExample {json} Input
     *    {
     *      "battle_id": 1,
     *      "accept": ture
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "User accepted/declined battle",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function updateBattleInvite(Request $request)
    {
        $battleId = (int) $request->get('battle_id');
        $accepted = filter_var($request->get('accept'), FILTER_VALIDATE_BOOLEAN);

        $battle = Battles::find($battleId);

        if ($accepted === false) {
            $battle->delete();
        } else {
            $battle->accepted = $accepted;
            $battle->accepted_at = date('Y-m-d H:i:s');
            $battle->save();

            $user = $battle->user;
            $opponentUser = $battle->opponentUser;

            // Send push notification to sender user (who created battle)
            $pushMessage = $opponentUser->first_name.' '.$opponentUser->last_name.' has '. ($accepted ? 'accepted' : 'declined') .' battle';
            
            $pushOpponentUser = User::select(['id', 'first_name', 'last_name', 'photo_url', \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points')])->where('id', $battle->opponent_user_id)->first();

            Push::send($battle->user_id, PushTypes::BATTLE_ACCEPT_DECLINE, $pushMessage, $pushOpponentUser);
        }

        return response()->json([
            'error' => 'false',
            'message' => 'User ' . ($accepted ? 'accepted' : 'declined') . ' battle',
            'data' => ['battle_id' => $battle->id, 'time' => strtotime($battle->created_at)]
        ]);
    }

    /**
     * @api {get} /battles/cancel/<battle_id> Cancel battle
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} battle_id Selected battle's id to cancel
     * @apiParamExample {json} Input
     *    {
     *      "battle_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Battle cancelled successfully",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function cancelBattle($battleId)
    {
        $battleId = (int) $battleId;

        if (empty($battleId))
            return null;

        $battle = Battles::find($battleId);

        if ($battle && $battle->user_id == \Auth::user()->id)
            $battle->delete();

        $user = $battle->user;
        $opponentUser = $battle->opponentUser;

        // Send Push Notification to opponent-user of battle
        $pushMessage = $user->first_name.' '.$user->last_name.' has cancelled battle';

        $pushOpponentUser = User::select(['id', 'first_name', 'last_name', 'photo_url', \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points')])->where('id', \Auth::user()->id)->first();

        Push::send($battle->opponent_user_id, PushTypes::BATTLE_CANCEL, $pushMessage, $pushOpponentUser);

        return response()->json([
            'error' => 'false',
            'message' => 'Battle cancelled successfully',
            'data' => ['battle_id' => $battle->id, 'time' => strtotime($battle->created_at)]
        ]);
    }

    /**
     * @api {get} /battles/combos Get list of available combos
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of combos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *      {
     *          "id": 1,
     *          "name": "Attack",
     *          "key_set": "1-2-SR-2-3-2-5-6-3-2",
     *          "keys": [
     *              "1", "2", "SR", "2", "3", "2", "5", "6", "3", "2"
     *          ],
     *      },
     *      {
     *          "id": 2,
     *          "name": "Crafty",
     *          "key_set": "1-2-5-7-3-2-SR-5-3-1",
     *          "keys": [
     *              "1", "2", "5", "7", "3", "2", "SR", "5", "3", "1"
     *          ],
     *      }
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getCombos()
    {
        $combos = Combos::select('*', \DB::raw('id as key_set'))->get()->toArray();

        foreach ($combos as $i => $combo) {
            $keySet = $combo['key_set'];

            $combos[$i]['keys'] = explode('-', $keySet);
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $combos]);
    }

    /**
     * @api {get} /battles/combo_sets Get list of combo-sets
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of combo-sets
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *      {
     *          "id": 1,
     *          "name": "AGGRESSOR",
     *          "combos": [
     *              "1", "2", "3"
     *          ],
     *      },
     *      {
     *          "id": 2,
     *          "name": "DEFENSIVE",
     *          "combos": [
     *              "1", "4", "5"
     *          ],
     *      }
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getComboSets()
    {
        $comboSets = [];
        $_comboSets = ComboSets::get();

        foreach ($_comboSets as $comboSet) {
            $_comboSet = $comboSet->toArray();
            $_comboSet['combos'] = $comboSet->combos->pluck('combo_id')->toArray();

            $comboSets[] = $_comboSet;
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $comboSets]);
    }

    /**
     * @api {get} /battles/workouts Get list of workouts
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Workouts
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *              "id": 1,
     *              "name": "Workout 1",
     *              "combos": [
     *                  [ 1, 2, 3 ],
     *                  [ 1, 4, 5 ],
     *                  [ 2, 3, 1 ],
     *                  [ 3, 4, 2 ],
     *                  [ 3, 1, 5 ]
     *              ]
     *          },
     *          {
     *              "id": 2,
     *              "name": "Workout 2",
     *              "combos": [
     *                  [ 1, 5, 3 ],
     *                  [ 2, 4, 3 ],
     *                  [ 5, 3, 4 ],
     *                  [ 1, 4, 2 ],
     *                  [ 3, 1, 5 ],
     *                  [ 2, 1, 5 ],
     *                  [ 3, 2, 5 ],
     *                  [ 3, 4, 1 ]
     *              ]
     *          }
     *      ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getWorkouts()
    {
        \DB::enableQueryLog();

        $workouts = [];
        $_workouts = Workouts::get();

        foreach ($_workouts as $workout) {
            $_workout = $workout->toArray();
            $combos = [];

            foreach ($workout->rounds as $round) {
                $combos[] = $round->combos->pluck('combo_id')->toArray();
            }

            $_workout['combos'] = $combos;

            $workouts[] = $_workout;
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $workouts]);
    }

    /**
     * @api {get} /battles/received  Get list of received battles
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
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
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data list of received battles
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *      {
     *          "battle_id": 12,
     *          "time": 1509530127,
     *          "opponent_user": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *             }
     *         },
     *        {
     *          "battle_id": 2,
     *          "time": 1509530127,
     *          "opponent_user": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *             }
     *         }
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getReceivedRequests(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $user_id = \Auth::user()->id;

        $battle_requests = Battles::select('battles.id as battle_id', 'user_id as opponent_user_id', 'first_name', 'last_name', 'photo_url', 'battles.created_at as time')
                        ->join('users', 'users.id', '=', 'battles.user_id')
                        ->where('opponent_user_id', $user_id)
                        ->where(function ($query) {
                            $query->whereNull('accepted')->orWhere('accepted', 0);
                        })
                        ->orderBy('battles.id', 'desc')
                        ->offset($offset)->limit($limit)->get()->toArray();
        $data = [];
        $i = 0;
        foreach ($battle_requests as $battle_request) {
            $data[$i]['battle_id'] = $battle_request['battle_id'];
            $data[$i]['time'] = strtotime($battle_request['time']);
            $following = UserConnections::where('follow_user_id', $battle_request['opponent_user_id'])
                            ->where('user_id', \Auth::user()->id)->exists();

            $follow = UserConnections::where('user_id', $battle_request['opponent_user_id'])
                            ->where('follow_user_id', \Auth::user()->id)->exists();

            $leaderboard = Leaderboard::where('user_id', $battle_request['opponent_user_id'])->first();
            $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

            $data[$i]['opponent_user'] = [
                'id' => $battle_request['opponent_user_id'],
                'first_name' => $battle_request['first_name'],
                'last_name' => $battle_request['last_name'],
                'photo_url' => $battle_request['photo_url'],
                'points' => (int) $points,
                'user_following' => (bool) $following,
                'user_follower' => (bool) $follow
            ];

            $i++;
        }
        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /battles/my_battles  Get list of sent request battles
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
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
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data list of sent request battles
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *    {
     *          "battle_id": 12,
     *          "time": 1509530127,
     *          "opponent_user": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *             }
     *         },
     *         {
     *          "battle_id": 12,
     *          "time": 1509530127,
     *          "opponent_user": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *             }
     *         }
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getMyBattles(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $user_id = \Auth::user()->id;
        $requested_by_opponent = Battles::select('battles.id as battle_id', 'user_id', 'opponent_user_id', 'battles.created_at  as time')
                        ->where('opponent_user_id', $user_id)
                        ->where(function ($query) {
                            $query->where('user_finished', 0)->orwhere('user_finished', null);
                        })
                        ->where(function ($query) {
                            $query->where('opponent_finished', 0)->orwhere('opponent_finished', null);
                        })
                        ->where(['accepted' => TRUE])
                        ->orderBy('battles.id', 'desc')->offset($offset)->limit($limit)->get()->toArray();
        $requested_by_user = Battles::select('battles.id as battle_id', 'user_id', 'opponent_user_id', 'battles.created_at  as time')
                        ->where('user_id', $user_id)
                        ->where(function ($query) {
                            $query->where('user_finished', 0)->orwhere('user_finished', null);
                        })
                        ->where(function ($query) {
                            $query->where('opponent_finished', 0)->orwhere('opponent_finished', null);
                        })
                        ->orderBy('battles.id', 'desc')->offset($offset)->limit($limit)->get()->toArray();

        $battle_requested = array_merge($requested_by_opponent, $requested_by_user);
        $data = [];
        $i = 0;
        foreach ($battle_requested as $battle_request) {
            $data[$i]['battle_id'] = $battle_request['battle_id'];
            $data[$i]['time'] = strtotime($battle_request['time']);
            $battle_request['opponent_user_id'] = ($battle_request['opponent_user_id'] == $user_id) ? $battle_request['user_id'] : $battle_request['opponent_user_id'];
            $following = UserConnections::where('follow_user_id', $battle_request['opponent_user_id'])
                            ->where('user_id', \Auth::user()->id)->exists();

            $follow = UserConnections::where('user_id', $battle_request['opponent_user_id'])
                            ->where('follow_user_id', \Auth::user()->id)->exists();

            $leaderboard = Leaderboard::where('user_id', $battle_request['opponent_user_id'])->first();
            $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

            $user = User::select('id', 'first_name', 'last_name', 'photo_url')
                            ->where(['id' => $battle_request['opponent_user_id']])->first();
            $data[$i]['opponent_user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'photo_url' => $user['photo_url'],
                'points' => (int) $points,
                'user_following' => (bool) $following,
                'user_follower' => (bool) $follow
            ];
            $i++;
        }
        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }

    /**
     * @api {get} /battles/finished  Get list of finished battles 
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
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
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data list of finished battles 
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *      {
     *          "battle_id": 4,
     *          "winner": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *          },
     *          "loser": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *          }
     *      },
     *      {
     *          "battle_id": 6,
     *          "winner": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *          },
     *          "loser": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *          }
     *      }
     *  ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getFinishedBattles(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $user_id = \Auth::user()->id;
        $battle_finished = Battles::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id')
                        ->where(['user_id' => $user_id])
                        ->orwhere(['opponent_user_id' => $user_id])
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->whereRaw('winner_user_id  != "" or null')
                        ->orderBy('battles.id', 'desc')
                        ->offset($offset)->limit($limit)->get()->toArray();

        $array = array();
        $i = 0;
        foreach ($battle_finished as $data) {
            if ($data['winner_user_id'] != '' and $data['winner_user_id'] != null) {
                $looserId = ($data['winner_user_id'] == $data['user_id']) ? $data['opponent_user_id'] : $data['user_id'];
                $array[$i]['battle_id'] = $data['battle_id'];
                $winner = User::select('id', 'first_name', 'last_name', 'photo_url')
                                ->where(['id' => $data['winner_user_id']])->first();
                $following = UserConnections::where('follow_user_id', $data['winner_user_id'])
                                ->where('user_id', \Auth::user()->id)->exists();

                $follow = UserConnections::where('user_id', $data['winner_user_id'])
                                ->where('follow_user_id', \Auth::user()->id)->exists();

                $leaderboard = Leaderboard::where('user_id', $data['winner_user_id'])->first();
                $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

                $array[$i]['winner'] = [
                    'id' => $winner['id'],
                    'first_name' => $winner['first_name'],
                    'last_name' => $winner['last_name'],
                    'photo_url' => $winner['photo_url'],
                    'points' => (int) $points,
                    'user_following' => (bool) $following,
                    'user_follower' => (bool) $follow
                ];

                $loser = User::select('id', 'first_name', 'last_name', 'photo_url')
                                ->where(['id' => $looserId])->first();
                $following_loss = UserConnections::where('follow_user_id', $looserId)
                                ->where('user_id', \Auth::user()->id)->exists();

                $follow_loss = UserConnections::where('user_id', $looserId)
                                ->where('follow_user_id', \Auth::user()->id)->exists();

                $leaderboard = Leaderboard::where('user_id', $looserId)->first();
                $points_loss = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

                $array[$i]['loser'] = [
                    'id' => $loser['id'],
                    'first_name' => $loser['first_name'],
                    'last_name' => $loser['last_name'],
                    'photo_url' => $loser['photo_url'],
                    'points' => (int) $points_loss,
                    'user_following' => (bool) $following_loss,
                    'user_follower' => (bool) $follow_loss
                ];
                $i++;
            }
        }
        return response()->json(['error' => 'false', 'message' => '', 'data' => $array]);
    }

    /**
     * @api {get} /battles/all  Get list of all battles 
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data list of all battles
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": {
     *          "received": [
     *                     {
     *                         "battle_id": 7,
     *                         "opponent_user": {
     *                             "id": 1,
     *                             "first_name": "Nawaz",
     *                             "last_name": "Me",
     *                             "photo_url": null,
     *                             "points": 2768,
     *                             "user_following": true,
     *                             "user_follower": false
     *                         }
     *                     },
     *                     {
     *                         "battle_id": 6,
     *                         "opponent_user": {
     *                             "id": 1,
     *                             "first_name": "Nawaz",
     *                             "last_name": "Me",
     *                             "photo_url": null,
     *                             "points": 2768,
     *                             "user_following": true,
     *                             "user_follower": false
     *                         }
     *                     }
     *                 ],
     *                 "my_battles": [
     *                     {
     *                         "battle_id": 32,
     *                         "opponent_user": {
     *                             "id": 1,
     *                             "first_name": "Nawaz",
     *                             "last_name": "Me",
     *                             "photo_url": null,
     *                             "points": 2768,
     *                             "user_following": true,
     *                             "user_follower": false
     *                         }
     *                     },
     *                   ],
     *                 "finished": [
     *                      {
     *                  "battle_id": 4,
     *                   "winner": {
     *                       "id": 33,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "points": 0,
     *                       "user_following": false,
     *                       "user_follower": false
     *                       },
     *                  "loser": {
     *                       "id": 33,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "points": 0,
     *                       "user_following": false,
     *                       "user_follower": false
     *                      }
     *                 },
     *           {
     *          "battle_id": 6,
     *                  "winner": {
     *                    "id": 33,
     *                    "first_name": "Anchal",
     *                    "last_name": "Gupta",
     *                    "photo_url": null,
     *                    "points": 0,
     *                    "user_following": false,
     *                    "user_follower": false
     *                   },
     *                "loser": {
     *                     "id": 33,
     *                     "first_name": "Anchal",
     *                     "last_name": "Gupta",
     *                     "photo_url": null,
     *                     "points": 0,
     *                    "user_following": false,
     *                    "user_follower": false
     *                   }
     *                   }
     *                 ]
     *             }
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getAllBattles(Request $request)
    {
        $array = array();
        $user_id = \Auth::user()->id;

        $battle_requests = Battles::select('battles.id as battle_id', 'user_id as opponent_user_id', 'first_name', 'last_name', 'photo_url', 'battles.created_at as time')
                        ->join('users', 'users.id', '=', 'battles.user_id')
                        ->where('opponent_user_id', $user_id)
                        ->where(function ($query) {
                            $query->whereNull('accepted')->orWhere('accepted', 0);
                        })
                        ->orderBy('battles.id', 'desc')->get()->toArray();
        $data = [];
        $i = 0;
        foreach ($battle_requests as $battle_request) {
            $data[$i]['battle_id'] = $battle_request['battle_id'];
            $data[$i]['time'] = strtotime($battle_request['time']);
            $following = UserConnections::where('follow_user_id', $battle_request['opponent_user_id'])
                            ->where('user_id', \Auth::user()->id)->exists();

            $follow = UserConnections::where('user_id', $battle_request['opponent_user_id'])
                            ->where('follow_user_id', \Auth::user()->id)->exists();

            $leaderboard = Leaderboard::where('user_id', $battle_request['opponent_user_id'])->first();
            $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

            $data[$i]['opponent_user'] = [
                'id' => $battle_request['opponent_user_id'],
                'first_name' => $battle_request['first_name'],
                'last_name' => $battle_request['last_name'],
                'photo_url' => $battle_request['photo_url'],
                'points' => (int) $points,
                'user_following' => (bool) $following,
                'user_follower' => (bool) $follow
            ];

            $i++;
        }
        $array['received'] = $data;

        $requested_by_opponent = Battles::select('battles.id as battle_id', 'user_id', 'opponent_user_id', 'battles.created_at  as time')
                        ->where('opponent_user_id', $user_id)
                        ->where(function ($query) {
                            $query->where('user_finished', 0)->orwhere('user_finished', null);
                        })
                        ->where(function ($query) {
                            $query->where('opponent_finished', 0)->orwhere('opponent_finished', null);
                        })
                        ->where(['accepted' => TRUE])
                        ->orderBy('battles.id', 'desc')->get()->toArray();
        $requested_by_user = Battles::select('battles.id as battle_id', 'user_id', 'opponent_user_id', 'battles.created_at  as time')
                        ->where('user_id', $user_id)
                        ->where(function ($query) {
                            $query->where('user_finished', 0)->orwhere('user_finished', null);
                        })
                        ->where(function ($query) {
                            $query->where('opponent_finished', 0)->orwhere('opponent_finished', null);
                        })
                        ->orderBy('battles.id', 'desc')->get()->toArray();

        $battle_requested = array_merge($requested_by_opponent, $requested_by_user);
        $my_battle_data = [];
        $j = 0;
        foreach ($battle_requested as $battle_request) {
            $my_battle_data[$j]['battle_id'] = $battle_request['battle_id'];
            $my_battle_data[$j]['time'] = strtotime($battle_request['time']);
            $battle_request['opponent_user_id'] = ($battle_request['opponent_user_id'] == $user_id) ? $battle_request['user_id'] : $battle_request['opponent_user_id'];
            $following = UserConnections::where('follow_user_id', $battle_request['opponent_user_id'])
                            ->where('user_id', \Auth::user()->id)->exists();

            $follow = UserConnections::where('user_id', $battle_request['opponent_user_id'])
                            ->where('follow_user_id', \Auth::user()->id)->exists();

            $leaderboard = Leaderboard::where('user_id', $battle_request['opponent_user_id'])->first();
            $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;
            
            $user = User::select('id', 'first_name', 'last_name', 'photo_url')
                            ->where(['id' => $battle_request['opponent_user_id']])->first();
            $my_battle_data[$j]['opponent_user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'photo_url' => $user['photo_url'],
                'points' => (int) $points,
                'user_following' => (bool) $following,
                'user_follower' => (bool) $follow
            ];
            $j++;
        }
        $array['my_battles'] = $my_battle_data;

        $battle_finished = Battles::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id')
                        ->where(['user_id' => $user_id])
                        ->orwhere(['opponent_user_id' => $user_id])
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->whereRaw('winner_user_id  != "" or null')
                        ->orderBy('battles.id', 'desc')
                        ->get()->toArray();

        $finished = array();
        $k = 0;
        foreach ($battle_finished as $data) {
            if ($data['winner_user_id'] != '' and $data['winner_user_id'] != null) {
                $looserId = ($data['winner_user_id'] == $data['user_id']) ? $data['opponent_user_id'] : $data['user_id'];
                $finished[$k]['battle_id'] = $data['battle_id'];
                $winner = User::select('id', 'first_name', 'last_name', 'photo_url')
                                ->where(['id' => $data['winner_user_id']])->first();
                $following = UserConnections::where('follow_user_id', $data['winner_user_id'])
                                ->where('user_id', \Auth::user()->id)->exists();

                $follow = UserConnections::where('user_id', $data['winner_user_id'])
                                ->where('follow_user_id', \Auth::user()->id)->exists();

                $leaderboard = Leaderboard::where('user_id', $data['winner_user_id'])->first();
                $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

                $finished[$k]['winner'] = [
                    'id' => $winner['id'],
                    'first_name' => $winner['first_name'],
                    'last_name' => $winner['last_name'],
                    'photo_url' => $winner['photo_url'],
                    'points' => (int) $points,
                    'user_following' => (bool) $following,
                    'user_follower' => (bool) $follow
                ];

                $loser = User::select('id', 'first_name', 'last_name', 'photo_url')
                                ->where(['id' => $looserId])->first();
                $following_loss = UserConnections::where('follow_user_id', $looserId)
                                ->where('user_id', \Auth::user()->id)->exists();

                $follow_loss = UserConnections::where('user_id', $looserId)
                                ->where('follow_user_id', \Auth::user()->id)->exists();

                $leaderboard = Leaderboard::where('user_id', $looserId)->first();
                $points_loss = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

                $finished[$k]['loser'] = [
                    'id' => $loser['id'],
                    'first_name' => $loser['first_name'],
                    'last_name' => $loser['last_name'],
                    'photo_url' => $loser['photo_url'],
                    'points' => (int) $points_loss,
                    'user_following' => (bool) $following_loss,
                    'user_follower' => (bool) $follow_loss
                ];
                $k++;
            }
        }
        $array['finished'] = $finished;

        return response()->json(['error' => 'false', 'message' => '', 'data' => $array]);
    }

}
