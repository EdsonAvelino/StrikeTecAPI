<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserAppTokens;
use GuzzleHttp\Client;

class PushController extends Controller
{
    /**
     * @api {post} /user/app_token Store app token
     * @apiGroup Push Notifications
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String="IOS","ANDROID"} os Mobile OS
     * @apiParam {String} token App generated token
     * @apiParamExample {json} Input
     *    {
     *      "os": "ANDROID",
     *      "token": "eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDEvYXV0aC9sb2dpbiIs"
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Token saved successfully"
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "What error is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function storeAppToken(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'os'    => 'required',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->first('email'))
                return response()->json(['error' => 'true', 'message' =>  $errors->first('os')]);
            else
                return response()->json(['error' => 'true', 'message' =>  $errors->first('token')]);
        }

        $appToken = UserAppTokens::create([
            'user_id' => \Auth::user()->id,
            'os' => strtoupper($request->get('os')),
            'token' => $request->get('token'),
        ]);

        return response()->json(['error' => 'false', 'message' => 'Token saved successfully']);
    }

    public function testPush()
    {
        $client = new Client(['base_uri' => 'https://fcm.googleapis.com']);

        $token = 'eWAyP3HAnsU:APA91bFUBqI7503M-tKXlg2VqOilR0xTpE1OEZkq6QDf0CN37YnFDmbCb3AbGXEKgMzya_a9vXfw3v5WbeHA7HDexF6D_bhFltL_TeanHbEUh1lP2cwUuETG44EvAZhkDXBuuyKnvRXG';

        $body = ['to' => $token,
                        'data' => [
                            'message' => 'Hey there! This is push notification test.'
                        ]
                    ];

        $response = $client->request('post', '/fcm/send', [
                        'headers' => [
                            'Authorization' => 'key=' . env('GOOGLE_FCM_SERVER_KEY'),
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode($body)
                    ]);
        $respContent = json_decode($response->getBody()->getContents());
        \Log::info("Resp=" . $response->getStatusCode());

        if (($response->getStatusCode() == 200) && ($respContent->failure == 1)) {
            $result = current($respContent->results);

             \Log::info("Failure=" . $result->error);

             return $result->error;
        } else {
            return 'Push notification success.';
        }
    }
}
