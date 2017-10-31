<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Battles;
use App\Combos;
use App\ComboSets;
use App\Workouts;

use App\Helpers\Push;

class BattleController extends Controller
{
    /**
     * @api {post} /battles Send battle invite
     * @apiGroup Battles
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
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

        // Send Push Notification
        // Push::send($opponentUserId);

        return response()->json(['error' => 'false', 'message' => 'User invited for battle successfully']);
    }

    /**
     * @api {get} /battles/<battle_id> Get battle details
     * @apiGroup Battles
     * @apiHeader {String} authorization Authorization value
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
     *          "data": {
     *              "id": 3,
     *              "name": "Left overs",
     *              "key_set": "1-3-5-5-3-1-5-3-3-1",
     *              "keys": [
     *                "1", "3", "5", "5", "3", "1", "5", "3", "3", "1"
     *              ],
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

        $battle = $_battle->toArray();
        $battle['data'] = $battleData;

        return response()->json(['error' => 'false', 'message' => '', 'data' => $battle]);
    }

    /**
     * @api {get} /battles/resend/<battle_id> Resend battle invite
     * @apiGroup Battles
     * @apiHeader {String} authorization Authorization value
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

        if (empty($battleId)) return null;

        $battle = Battles::find($battleId);

        // Send Push Notification
        // Push::send($battle->opponent_user_id);
    }

    /**
     * @api {post} /battles/accept_decline Accept or Decline battle invite
     * @apiGroup Battles
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
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

        if ($accepted) {
            $battle->update(['accepted' => $accepted, 'accepted_at' => date('Y-m-d H:i:s')]);
        } else {
            $battle->delete();
        }

        return response()->json(['error' => 'false', 'message' => 'User '. ($accepted ? 'accepted' : 'declined') .' battle']);
    }

    /**
     * @api {get} /battles/cancel/<battle_id> Cancel battle
     * @apiGroup Battles
     * @apiHeader {String} authorization Authorization value
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

        if (empty($battleId)) return null;

        $battle = Battles::find($battleId);

        if ($battle && $battle->user_id == \Auth::user()->id)
            $battle->delete();

        return response()->json(['error' => 'false', 'message' => 'Battle cancelled successfully']);
    }

    /**
     * @api {get} /battles/combos Get list of available combos
     * @apiGroup Battles
     * @apiHeader {String} authorization Authorization value
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
     * @apiHeader {String} authorization Authorization value
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
     * @apiHeader {String} authorization Authorization value
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
}
