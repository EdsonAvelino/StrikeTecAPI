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
        $battle = Battles::create([
            'user_id' => \Auth::user()->id,
            'opponent_user_id' => (int) $request->get('opponent_user_id'),
            'type_id' => (int) $request->get('plan_id'),
            'plan_id' => (int) $request->get('type_id')
        ]);

        // TODO Send Push Notification
        // Push::send();

        return response()->json(['error' => 'false', 'message' => 'User invited for battle successfully']);
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

        Battles::where('id', $battleId)->update(['accepted' => $accepted, 'accepted_at' => date('Y-m-d H:i:s')]);

        return response()->json(['error' => 'false', 'message' => 'User '. ($accepted ? 'accepted' : 'declined') .' battle']);
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
     *       {
     *          "id": 1,
     *          "name": "Attack",
     *          "description": null,
     *          "key_set": "1-2-SR-2-3-2-5-6-3-2"
     *        },
     *        {
     *          "id": 2,
     *          "name": "Crafty",
     *          "description": null,
     *          "key_set": "1-2-SR-2-3-2-5-6-3-2"
     *        }
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
        $combos = Combos::select('*', \DB::raw('id as key_set'))->get();
        
        return response()->json(['error' => 'false', 'message' => '', 'data' => $combos->toArray()]);
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
     *         {
     *          "id": 1,
     *          "name": "AGGRESSOR",
     *          "combos": [
     *            {
     *              "id": 1,
     *              "combo_set_id": 1,
     *              "combo_id": 1,
     *              "combo": {
     *              "id": 1,
     *              "name": "Attack",
     *              "key_set": "1-2-SR-2-3-2-5-6-3-2"
     *              }
     *          },
     *            {
     *              "id": 1,
     *              "combo_set_id": 1,
     *              "combo_id": 2,
     *              "combo": {
     *              "id": 2,
     *              "name": "Crafty",
     *              "key_set": ""
     *          }
     *          },
     *            {
     *               "id": 1,
     *               "combo_set_id": 1,
     *               "combo_id": 3,
     *               "combo": {
     *               "id": 3,
     *               "name": "Left overs",
     *               "key_set": ""
     *              }
     *          }
     *          ],
     *          },
     *            {
     *          "id": 2,
     *          "name": "DEFENSIVE",
     *          "combos": [
     *            {
     *              "id": 2,
     *              "combo_set_id": 2,
     *              "combo_id": 2,
     *              "combo": {
     *              "id": 2,
     *              "name": "Crafty",
     *              "key_set": ""
     *          }
     *          },
     *            {
     *              "id": 2,
     *              "combo_set_id": 2,
     *              "combo_id": 4,
     *              "combo": {
     *              "id": 4,
     *              "name": "Defensive",
     *              "key_set": ""
     *          }
     *          },
     *            {
     *               "id": 2,
     *               "combo_set_id": 2,
     *               "combo_id": 5,
     *               "combo": {
     *               "id": 5,
     *               "name": "Movement",
     *               "key_set": ""
     *          }
     *          }
     *          ],
     *          }
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
        $_comboSets = ComboSets::with(['combos.combo' => function($query) {
            $query->select('*', \DB::raw('id as key_set'));
        }])->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $_comboSets->toArray()]);
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
     *     {
     *    "error": "false",
     *    "message": "",
     *    "data": [{
     *        "id": 1,
     *        "name": "Workout 1",
     *        "rounds": [{
     *                "id": 1,
     *                "workout_id": 1,
     *                "name": "Round 1",
     *                "combos": [{
     *                        "id": 1,
     *                        "workout_round_id": 1,
     *                        "combo_id": 1,
     *                        "key_set": "1-2-SR-2-3-2-5-6-3-2"
     *                    },
     *                    {
     *                        "id": 2,
     *                        "workout_round_id": 1,
     *                        "combo_id": 2,
     *                        "key_set": "1-2-5-7-3-2-SR-5-3-1"
     *                    },
     *                    {
     *                        "id": 3,
     *                        "workout_round_id": 1,
     *                        "combo_id": 3,
     *                        "key_set": "1-3-5-5-3-1-5-3-3-1"
     *                    }
     *                ],
     *            },
     *            {
     *                "id": 2,
     *                "workout_id": 1,
     *                "name": "Round 2",
     *                "combos": [{
     *                        "id": 4,
     *                        "workout_round_id": 2,
     *                        "combo_id": 1,
     *                        "key_set": "1-2-SR-2-3-2-5-6-3-2"
     *                    },
     *                    {
     *                        "id": 5,
     *                        "workout_round_id": 2,
     *                        "combo_id": 4,
     *                        "key_set": "1-2-6-7-3-2-5-1-3-2"
     *                    },
     *                    {
     *                        "id": 6,
     *                        "workout_round_id": 2,
     *                        "combo_id": 5,
     *                        "key_set": "3-5-4-1-5-2-1-6-3-2"
     *                    }
     *                ],
     *            },
     *            {
     *                "id": 3,
     *                "workout_id": 1,
     *                "name": "Round 3",
     *                "combos": [{
     *                        "id": 7,
     *                        "workout_round_id": 3,
     *                        "combo_id": 2,
     *                        "key_set": "1-2-5-7-3-2-SR-5-3-1"
     *                    },
     *                    {
     *                        "id": 8,
     *                        "workout_round_id": 3,
     *                        "combo_id": 3,
     *                        "key_set": "1-3-5-5-3-1-5-3-3-1"
     *                    },
     *                    {
     *                        "id": 9,
     *                        "workout_round_id": 3,
     *                        "combo_id": 1,
     *                        "key_set": "1-2-SR-2-3-2-5-6-3-2"
     *                    }
     *                ],
     *            },
     *            {
     *                "id": 4,
     *                "workout_id": 1,
     *                "name": "Round 4",
     *                "combos": [{
     *                        "id": 10,
     *                        "workout_round_id": 4,
     *                        "combo_id": 3,
     *                        "key_set": "1-3-5-5-3-1-5-3-3-1"
     *                    },
     *                    {
     *                        "id": 11,
     *                        "workout_round_id": 4,
     *                        "combo_id": 4,
     *                        "key_set": "1-2-6-7-3-2-5-1-3-2"
     *                    },
     *                    {
     *                        "id": 12,
     *                        "workout_round_id": 4,
     *                        "combo_id": 2,
     *                        "key_set": "1-2-5-7-3-2-SR-5-3-1"
     *                    }
     *                ],
     *            },
     *            {
     *                "id": 5,
     *                "workout_id": 1,
     *                "name": "Round 5",
     *                "combos": [{
     *                        "id": 13,
     *                        "workout_round_id": 5,
     *                        "combo_id": 3,
     *                        "key_set": "1-3-5-5-3-1-5-3-3-1"
     *                    },
     *                    {
     *                        "id": 14,
     *                        "workout_round_id": 5,
     *                        "combo_id": 1,
     *                        "key_set": "1-2-SR-2-3-2-5-6-3-2"
     *                    },
     *                    {
     *                        "id": 15,
     *                        "workout_round_id": 5,
     *                        "combo_id": 5,
     *                        "key_set": "3-5-4-1-5-2-1-6-3-2"
     *                    }
     *                ],
     *            }
     *        ]
     *    }]
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

        $_workouts = Workouts::with(['rounds.combos' => function($query) {
            $query->select('*', \DB::raw('combo_id as key_set'));
        }])->get();

        // print_r(\DB::getQueryLog());
        // die();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $_workouts->toArray()]);
    }
}
