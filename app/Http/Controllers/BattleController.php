<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Battles;
use App\Combos;
use App\ComboSets;
use App\Workouts;
use App\User;
use App\Helpers\Push;
use App\Helpers\PushTypes;

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
        $pushMessage = \Auth::user()->first_name . ' ' . \Auth::user()->last_name . ' has invited you for battle';

        // Push::send($opponentUserId, PushTypes::BATTLE_INVITE, $pushMessage, $pushOpponentUser);
        Push::send(PushTypes::BATTLE_INVITE, $opponentUserId, \Auth::user()->id, $pushMessage, ['battle_id' => $battle->id]);

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
     *          "sender_user_id": 1,
     *          "battle_result": {
     *              "winner": {
     *                  "id": 31,
     *                  "first_name": "Test",
     *                  "last_name": "Test",
     *                  "photo_url": null,
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 984,
     *                  "avg_speed": 24,
     *                  "avg_force": 431,
     *                  "punches_count": 9
     *              },
     *              "looser": {
     *                  "id": 7,
     *                  "first_name": "Qiang",
     *                  "last_name": "Hu",
     *                  "photo_url": null,
     *                  "user_following": true,
     *                  "user_follower": true,
     *                  "points": 2308,
     *                  "avg_speed": 21,
     *                  "avg_force": 354,
     *                  "punches_count": 9
     *              }
     *          }
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

        $opponentUser = User::get($opponentUserId);

        $battle = $_battle->toArray();

        $battle['opponent_user'] = $opponentUser->toArray();

        // ID of user who created the battle
        $battle['sender_user_id'] = $_battle->user_id;

        // TODO
        // Battle result
        $battle['battle_result'] = Battles::getResult($battleId);

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
        $pushMessage = $user->first_name . ' ' . $user->last_name . ' has invited you for battle';
        // $pushOpponentUser = User::get();
        // Push::send($battle->opponent_user_id, PushTypes::BATTLE_RESEND, $pushMessage, $pushOpponentUser);

        Push::send(PushTypes::BATTLE_RESEND, $battle->opponent_user_id, \Auth::user()->id, $pushMessage, ['battle_id' => $battle->id]);

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

        $user = $battle->user;
        $opponentUser = $battle->opponentUser;

        // Send push notification to sender user (who created battle)
        $pushMessage = $opponentUser->first_name . ' ' . $opponentUser->last_name . ' has ' . ($accepted ? 'accepted' : 'declined') . ' battle';

        // $pushOpponentUser = User::get($battle->opponent_user_id);

        $pushType = ($accepted) ? PushTypes::BATTLE_ACCEPT : PushTypes::BATTLE_DECLINE;

        // Push::send($battle->user_id, $pushType, $pushMessage, $pushOpponentUser);
        Push::send($pushType, $battle->user_id, $battle->opponent_user_id, $pushMessage, ['battle_id' => $battle->id]);

        if ($accepted === false) {
            $battle->delete();
        } else {
            $battle->accepted = $accepted;
            $battle->accepted_at = date('Y-m-d H:i:s');
            $battle->save();
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
        $pushMessage = $user->first_name . ' ' . $user->last_name . ' has cancelled battle';

        // $pushOpponentUser = User::get(\Auth::user()->id);
        // Push::send($battle->opponent_user_id, PushTypes::BATTLE_CANCEL, $pushMessage, $pushOpponentUser);

        Push::send(PushTypes::BATTLE_CANCEL, $battle->opponent_user_id, \Auth::user()->id, $pushMessage, ['battle_id' => $battle->id]);

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

        $userId = \Auth::user()->id;

        $battle_requests = Battles::select('battles.id as battle_id', 'user_id as opponent_user_id', 'first_name', 'last_name', 'photo_url', 'battles.created_at as time')
                        ->join('users', 'users.id', '=', 'battles.user_id')
                        ->where('opponent_user_id', $userId)
                        ->where(function ($query) {
                            $query->whereNull('accepted')->orWhere('accepted', 0);
                        })
                        ->orderBy('battles.updated_at', 'desc')
                        ->offset($offset)->limit($limit)->get()->toArray();
        $data = [];
        $i = 0;
        foreach ($battle_requests as $battle_request) {
            $data[$i]['battle_id'] = $battle_request['battle_id'];
            $data[$i]['time'] = strtotime($battle_request['time']);
            $data[$i]['opponent_user'] = User::get($battle_request['opponent_user_id']);
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
        $userId = \Auth::user()->id;
        $requested_by_opponent = Battles::select('battles.id as battle_id', 'user_id', 'opponent_user_id', 'battles.created_at  as time')
                        ->where(function ($query) use($userId) {
                            $query->where('opponent_user_id', $userId)->where('accepted', TRUE)->where(function ($query1) use($userId) {
                                $query1->where('user_finished', 0)->orWhereNull('user_finished')->orWhere('opponent_finished', 0)->orWhereNull('opponent_finished');
                            });
                        })
                        ->orWhere(function ($query) use($userId) {
                            $query->where('user_id', $userId)->where(function ($query1) use($userId) {
                                $query1->where('user_finished', 0)->orWhereNull('user_finished')->orWhere('opponent_finished', 0)->orWhereNull('opponent_finished');
                            });
                        })
                        ->orderBy('battles.updated_at', 'desc')->offset($offset)->limit($limit)->get()->toArray();
        $data = [];
        $i = 0;
        foreach ($requested_by_opponent as $battle_request) {
            $data[$i]['battle_id'] = $battle_request['battle_id'];
            $data[$i]['time'] = strtotime($battle_request['time']);
            $battle_request['opponent_user_id'] = ($battle_request['opponent_user_id'] == $userId) ? $battle_request['user_id'] : $battle_request['opponent_user_id'];
            $data[$i]['opponent_user'] = User::get($battle_request['opponent_user_id']);
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

        $userId = \Auth::user()->id;
        
        $finishedBattles = Battles::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id', 'user_finished_at', 'opponent_finished_at')
                        ->where(function ($query)use($userId) {
                            $query->where(['user_id' => $userId])->orWhere(['opponent_user_id' => $userId]);
                        })
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->orderBy('battles.updated_at', 'desc')
                        ->offset($offset)->limit($limit)->get();
        
        $data = [];

        foreach ($finishedBattles as $battle) {
            $battleResult = Battles::getResult($battle->battle_id);

            if (!$battleResult['winner'] || !$battleResult['loser'])
                continue;
            else
                $data[] = array_merge(['battle_id' => $battle->battle_id], $battleResult);
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
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
        $userId = \Auth::user()->id;

        $battle_requests = Battles::select('battles.id as battle_id', 'user_id as opponent_user_id', 'first_name', 'last_name', 'photo_url', 'battles.created_at as time')
                        ->join('users', 'users.id', '=', 'battles.user_id')
                        ->where('opponent_user_id', $userId)
                        ->where(function ($query) {
                            $query->whereNull('accepted')->orWhere('accepted', 0);
                        })
                        ->orderBy('battles.updated_at', 'desc')->get()->toArray();
        $data = [];
        $i = 0;
        foreach ($battle_requests as $battle_request) {
            $data[$i]['battle_id'] = $battle_request['battle_id'];
            $data[$i]['time'] = strtotime($battle_request['time']);
            $data[$i]['opponent_user'] = User::get($battle_request['opponent_user_id']);
            $i++;
        }
        $array['received'] = $data;

        $requested_by_opponent = Battles::select('battles.id as battle_id', 'user_id', 'opponent_user_id', 'battles.created_at  as time')
                        ->where(function ($query) use($userId) {
                            $query->where('opponent_user_id', $userId)->where('accepted', TRUE)->where(function ($query1) use($userId) {
                                $query1->where('user_finished', 0)->orWhereNull('user_finished')->orWhere('opponent_finished', 0)->orWhereNull('opponent_finished');
                            });
                        })
                        ->orWhere(function ($query) use($userId) {
                            $query->where('user_id', $userId)->where(function ($query1) use($userId) {
                                $query1->where('user_finished', 0)->orWhereNull('user_finished')->orWhere('opponent_finished', 0)->orWhereNull('opponent_finished');
                            });
                        })
                        ->orderBy('battles.updated_at', 'desc')->get()->toArray();
        $my_battle_data = [];
        $j = 0;
        foreach ($requested_by_opponent as $battle_request) {
            $my_battle_data[$j]['battle_id'] = $battle_request['battle_id'];
            $my_battle_data[$j]['time'] = strtotime($battle_request['time']);
            $battle_request['opponent_user_id'] = ($battle_request['opponent_user_id'] == $userId) ? $battle_request['user_id'] : $battle_request['opponent_user_id'];
            $my_battle_data[$j]['opponent_user'] = User::get($battle_request['opponent_user_id']);
            $j++;
        }
        $array['my_battles'] = $my_battle_data;

        $battle_finished = Battles::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id', 'user_finished_at', 'opponent_finished_at')
                        ->where(function ($query)use($userId) {
                            $query->where(['user_id' => $userId])->orWhere(['opponent_user_id' => $userId]);
                        })
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->orderBy('battles.updated_at', 'desc')
                        ->get()->toArray();
        $finished = array();
        $k = 0;
        foreach ($battle_finished as $data) {
            if (empty($data['winner_user_id'])) {
                $data['winner_user_id'] = (strtotime($data['user_finished_at']) < strtotime($data['opponent_finished_at'])) ? $data['user_id'] : $data['opponent_user_id'];
            }
            $looserId = ($data['winner_user_id'] == $data['user_id']) ? $data['opponent_user_id'] : $data['user_id'];
            $finished[$k]['battle_id'] = $data['battle_id'];
            $finished[$k]['winner'] = User::get($data['winner_user_id']);
            $finished[$k]['loser'] = User::get($looserId);
            $k++;
        }
        $array['finished'] = $finished;

        return response()->json(['error' => 'false', 'message' => '', 'data' => $array]);
    }

    /**
     * @api {post} /combos/audio Set audio in combos
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *         "Content-Type": "multipart/form-data"
     *         "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} combo_id Combo id
     * @apiParam {file} audio_file recorded audio file need to be saved
     * @apiParamExample {json} Input
     *    {
     *      "combo_id": 1,
     *      "audio_file": abc.mp3
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} Data list of combos with audio
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *  {
     *     "error": "false",
     *     "message": "Audio uploaded successfully!",
     *      "data": {
     *          "id": 2,
     *          "name": "Crafty",
     *          "user_id": 7,
     *          "audio": "http://striketec.dev/storage/comboAudio/SampleAudi-1510313064.mp3"
     *      }
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function saveAudio(Request $request)
    {
        $userId = \Auth::user()->id;
        $comboId = $request->combo_id;
        $combo = Combos::findOrFail($comboId);
        $image = $combo->audio;
        $file = $request->file('audio_file');
        if ($image != "") {
            $url = url() . '/storage';
            $pathToFile = str_replace($url, storage_path(), $image);
            if (file_exists($pathToFile)) {
                unlink($pathToFile); //delete earlier audio
            }
        }
        $dest = 'storage/comboAudio';
        if ($request->hasFile('audio_file')) {
            $imgOrgName = $file->getClientOriginalName();
            $nameWithoutExt = pathinfo($imgOrgName, PATHINFO_FILENAME);
            $ext = pathinfo($imgOrgName, PATHINFO_EXTENSION);
            $imgOrgName = $nameWithoutExt . '-' . time() . '.' . $ext;  //make audio name unique
            $file->move($dest, $imgOrgName);
            $gif_path = url() . '/' . $dest . '/' . $imgOrgName; // path to be inserted in table
            $combo->audio = $gif_path;
            $combo->user_id = $userId;
            $combo->save();
        }
        return response()->json(['error' => 'false', 'message' => 'Audio uploaded successfully!', 'data' => $combo]);
    }

    /**
     * @api {get} /battles/combos/audio Get list of available combos with audio
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
     *          "audio": "http://striketec.dev/storage/comboAudio/SampleAudi-1510313064.mp3"
     *      },
     *      {
     *          "id": 2,
     *          "name": "Crafty",
     *          "audio": "http://striketec.dev/storage/comboAudio/SampleAudi-1510313064.mp3"
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
    public function getCombosAudio()
    {
        $combos = Combos::select('id', 'name', 'audio')->get()->toArray();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $combos]);
    }

    /**
     * @api {get} /battles/user/finished  Get list of finished battles by user
     * @apiGroup Battles
     * @apiHeader {String} Authorization Authorization Token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} user_id User id 
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "user_id": 20,
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
    public function getUsersFinishedBattles(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $userId = $request->get('user_id');

        $finishedBattles = Battles::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id', 'user_finished_at', 'opponent_finished_at')
                        ->where(function ($query)use($userId) {
                            $query->where(['user_id' => $userId])->orWhere(['opponent_user_id' => $userId]);
                        })
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->orderBy('battles.updated_at', 'desc')
                        ->offset($offset)->limit($limit)->get();
        $data = [];
        
        foreach ($finishedBattles as $battle) {
            $battleResult = Battles::getResult($battle->battle_id);

            if (!$battleResult['winner'] || !$battleResult['loser'])
                continue;
            else
                $data[] = array_merge(['battle_id' => $battle->battle_id], $battleResult);
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
    }
}