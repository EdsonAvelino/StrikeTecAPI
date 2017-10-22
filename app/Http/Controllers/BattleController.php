<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BattleCombos;

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
     * @apiParam {String} combo_id Selected combo id
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
    public function postInvite(Request $request)
    {
        $toUserId = (int) $request->get('to_user_id');
        $comboId = (int) $request->get('combo_id');
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
        $_combos = BattleCombos::get();
        
        $combos = [];

        foreach ($_combos as $combo) {
            $keySet = [];
            foreach ($combo->keySet as $key) {
                $keySet[] = $key->punch_type_id;
            }

            $_combo = $combo->toArray();
            $_combo['key_set'] = implode('-', $keySet);

            $combos[] = $_combo;
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $combos]);
    }
}
