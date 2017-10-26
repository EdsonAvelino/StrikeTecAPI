<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Settings;


class SettingController extends Controller 
{

    /**
     * @api {post} /notification/settings Notification Settings
     * @apiGroup Notification Settings
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} action e.g. new_challenges , battle_update, tournaments_update, games_update, new_message, friend_invites, sensor_connectivity, app_updates, striketec_promos, striketec_news
     * @apiParam {Number} values e.g. 0,1
     * @apiParamExample {json} Input
     *    {
     *      "action": "new challenges"
     *      "value" : 1
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *       "error": "false",
     *       "message": "Notification settings has been updated Successfully."
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function updateSettings(Request $request) 
    {
        
        $user_id = \Auth::user()->id;
        $action = $request->get('action');
        $value = $request->get('value');

        //check if the settings are exist in settings table
        $chk_exist_user = Settings::where('user_id', $user_id)->exists();

        if ($chk_exist_user == false) {
            Settings::create(['user_id' => $user_id]);
        }

        switch ($action) {

            case "new_challenges":
                Settings::where('user_id', $user_id)->update(
                        ['new_challenges' => $value]
                );
                break;

            case "battle_update":
                Settings::where('user_id', $user_id)->update(
                        ['battle_update' => $value]
                );
                break;

            case "tournaments_update":
                Settings::where('user_id', $user_id)->update(
                        ['tournaments_update' => $value]
                );
                break;

            case "games_update":
                Settings::where('user_id', $user_id)->update(
                        ['games_update' => $value]
                );
                break;

            case "new_message":
                Settings::where('user_id', $user_id)->update(
                        ['new_message' => $value]
                );
                break;

            case "friend_invites":
                Settings::where('user_id', $user_id)->update(
                        ['friend_invites' => $value]
                );
                break;

            case "sensor_connectivity":
                Settings::where('user_id', $user_id)->update(
                        ['sensor_connectivity' => $value]
                );
                break;

            case "striketec_promos":
                Settings::where('user_id', $user_id)->update(
                        ['striketec_promos' => $value]
                );
                break;
            case "app_updates":
                Settings::where('user_id', $user_id)->update(
                        ['app_updates' => $value]
                );
                break;

            case "striketec_news":
                Settings::where('user_id', $user_id)->update(
                        ['striketec_news' => $value]
                );
                break;
        }
        return response()->json(['error' => 'false', 'message' => 'Notification settings have been updated Successfully.']);
    }

    /**
     * @api {get} /notification/settings all the settings of user
     * @apiGroup Notification Settings
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of Notification Settings
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *              "user_id": 7,
     *              "new_challenges": 1,
     *              "battle_update": 0,
     *              "tournaments_update": 0,
     *              "games_update": 0,
     *              "new_message": 0,
     *              "friend_invites": 0,
     *              "sensor_connectivity": 0,
     *              "app_updates": 0,
     *              "striketec_promos": 0,
     *              "striketec_news": 1
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
    public function getSettings(Request $request) 
    {

        $userId = \Auth::user()->id;
        $SubscriptionsList = Settings::where('user_id', $userId)->select('user_id', 'new_challenges', 'battle_update', 'tournaments_update', 'games_update', 'new_message', 'friend_invites', 'sensor_connectivity', 'app_updates', 'striketec_promos', 'striketec_news')
                ->get();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $SubscriptionsList]);
    }

}
