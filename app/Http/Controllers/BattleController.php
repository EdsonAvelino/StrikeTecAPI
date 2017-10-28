<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Battles;
use App\Combos;
use App\ComboSets;

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
     * @apiParam {String} to_user_id To which user send invite
     * @apiParam {String} [combo_id] Selected combo id
     * @apiParam {String} [combo_set_id] Selected combo-set id
     * @apiParam {String} [workout_id] Scripted wourkout round's id
     * @apiParam {String} type_id Type could be from { 3 = Combo, 4 = Combo-Set, 5=Workout }
     * @apiParam {String} description Description what user added while creating battle
     * @apiParamExample {json} Input
     *    {
     *      "to_user_id": 12,
     *      "combo_id": 1
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "User invited successfully",
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
        $toUserId = (int) $request->get('to_user_id');

        $battle = Battles::create([
            'from_user_id' => \Auth::user()->id,
            'to_user_id' => $toUserId,
            'type_id' => $request->get('type_id'),
            'combo_id' => $request->get('combo_id') ?? null,
            'combo_set_id' => $request->get('combo_set_id') ?? null,
            'workout_id' => $request->get('workout_id') ?? null,
            'description' => $request->get('description')
        ]);

        // Push::send();
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
}
