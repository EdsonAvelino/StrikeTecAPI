<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Chats;
use App\ChatMessages;
use App\User;
use App\UserConnections;
use App\Leaderboard;

use App\Helpers\Push;
use App\Helpers\PushTypes;

class ChatController extends Controller
{

    /**
     * @api {post} /chat/send Send new message
     * @apiGroup Chat
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} user_id connection user id
     * @apiParam {String} message message to send user 
     * @apiParamExample {json} Input
     *    {
     *      "user_id": 1,
     *      "message": 'test message'
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": {
     *           "message_id": 47,
     *           "sender_id": 7,
     *           "message": "this is the test message 5",
     *           "read": false,
     *           "send_time": 1510569372
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
    public function sendMessage(Request $request)
    {
        $sender_id = \Auth::user()->id;
        $user_id = $request->user_id;
        $message = $request->message;
        
        $chat_id = $this->getChatid($user_id);
        
        $chat_id = ChatMessages::create([
            'user_id' => $sender_id,
            'read_flag' => FALSE,
            'message' => $message,
            'chat_id' => $chat_id
        ])->id;

        $chatResponse = ChatMessages::where('id', $chat_id)
                                    ->select('id as message_id', 'user_id as sender_id', 'message', 'read_flag as read', 'created_at as send_time')
                    ->first(); 

        $chatResponse->read = filter_var($chatResponse->read, FILTER_VALIDATE_BOOLEAN);
        $chatResponse->send_time = strtotime($chatResponse->send_time);

        $pushOpponentUser = User::get($sender_id);

        $pushMessage = 'You received new message from '.$pushOpponentUser->first_name.' '.$pushOpponentUser->last_name;

        Push::send($user_id, PushTypes::CHAT_SEND_MESSAGE, $pushMessage, $pushOpponentUser, ['message' => $chatResponse]);

        return response()->json(['error' => 'false', 'message' => '', 'data' => $chatResponse]);
    }

    /**
     * @api {post} /chat/read Read messages
     * @apiGroup Chat
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} message_id Message ID
     * @apiParamExample {json} Input
     *    {
     *      "message_id": 20,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Read.",
     *      "data": {
     *           "message_id": "6"
     *        }
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function readMessage(Request $request)
    {
        $message_id = $request->message_id;
        $user_id = \Auth::user()->id;

        $chatMessage = ChatMessages::where('id', $message_id)->where('user_id', '!=', $user_id)->first();
        $chatMessage->update(['read_flag' => 1]);

        if ($chatMessage->user_id != \Auth::user()->id) {
            $pushOpponentUser = User::get($user_id);

            $pushMessage = 'Read message';

            $chatResponse = ChatMessages::where('id', $chat_id)
                                    ->select('id as message_id', 'user_id as sender_id', 'message', 'read_flag as read', 'created_at as send_time')->first(); 

            $chatResponse->read = filter_var($chatResponse->read, FILTER_VALIDATE_BOOLEAN);
            $chatResponse->send_time = strtotime($chatResponse->send_time);

            Push::send($chatMessage->user_id, PushTypes::CHAT_READ_MESSAGE, $pushMessage, $pushOpponentUser, ['message_id' => $message_id]);
        }

        return response()->json(['error' => 'false', 'message' => "Read.", 'data' => ['message_id' => $message_id]]);
    }

    /**
     * @api {get} /chat/history Get all the messages of chat
     * @apiGroup Chat
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} user_id Connection user ID
     * @apiParam {Number} message_id Message ID if -1 list recent else record listed which created less then this time
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "user_id": 6,
     *      "message_id": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *         {
     *              "message_id": 27,
     *              "sender_id": 7,
     *              "read": false,
     *              "message": "this is the test message 2",
     *              "send_time": 1510136168
     *         },
     *         {
     *              "message_id": 27,
     *              "sender_id": 7,
     *              "read": false,
     *              "message": "this is the test message 2",
     *              "send_time": 1510136168
     *         },
     *         {
     *              "message_id": 27,
     *              "sender_id": 7,
     *              "read": false,
     *              "message": "this is the test message 2",
     *              "send_time": 1510136168
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
    public function chatHistory(Request $request)
    {
        $offset_message_id = (int) ($request->get('message_id') ? $request->get('message_id') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $connection_id = (int) $request->get('user_id');
        $chat_id = $this->getChatid($connection_id); 
        $chat_detail = ChatMessages::select('chat_messages.id as message_id', 'user_id as sender_id', 'read_flag as read', 'chat_id', 'message', 'chat_messages.created_at as send_time')
                        ->join('users', 'users.id', '=', 'chat_messages.user_id')
                        ->where('chat_id', $chat_id)
                        ->where(function($query) use ($offset_message_id)
                        {
                            if( $offset_message_id === -1 ) {
                                $query->where('chat_messages.id', '>=', $offset_message_id);
                            }else {
                                $query->where('chat_messages.id', '<=', $offset_message_id);
                            }
                        })
                        ->orderBy('chat_messages.created_at', 'desc')
                        ->limit($limit)->get();
        $chat = array();
        foreach ($chat_detail as $chat_details) {
            $chat[] = [
                'message_id' => $chat_details['message_id'],
                'sender_id' => $chat_details['sender_id'],
                'read' => (bool) $chat_details['read'],
                'message' => $chat_details['message'],
                'send_time' => strtotime($chat_details['send_time'])
            ];
        }
        return response()->json(['error' => 'false', 'message' => '', 'data' => $chat]);
    }

    /**
     * @api {get} /chat Get all the chats 
     * @apiGroup Chat
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
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *      {
     *             "opponent_user": {
     *                 "id": 12,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 3026,
     *                 "user_following": false,
     *                 "user_follower": false
     *             },
     *             "msg_time": "1510569372",
     *             "lst_msg": "yeshghgg",
     *             "unread_msg_count": 0
     *         },
     *         {
     *             "opponent_user": {
     *                 "id": 33,
     *                 "first_name": "Anchal",
     *                 "last_name": "Gupta",
     *                 "photo_url": null,
     *                 "points": 0,
     *                 "user_following": false,
     *                 "user_follower": false
     *             },
     *             "msg_time": "1510569372",
     *             "lst_msg": "yeshghgg",
     *             "unread_msg_count": 3
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
    public function chats(Request $request)
    {
        $user_id = \Auth::user()->id;
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $chat_list = Chats::select('user_one', 'user_two', 'id')
                        ->where('user_one', $user_id)
                        ->orwhere('user_two', $user_id)
                        ->orderBy('created_at', 'desc')
                        ->offset($offset)->limit($limit)->get()->all();
        $chat_count = 0;
        $chat = array();
        foreach ($chat_list as $data) {
            
            $chat_msg = ChatMessages::select('message', 'created_at as msg_time')
                            ->where('chat_id', $data['id'])
                            ->orderBy('chat_messages.created_at', 'desc')
                            ->offset(0)->limit(1)->get()->first();
            if($chat_msg){
                $opponent_id = ($data['user_one'] != $user_id) ? $data['user_one'] : $data['user_two'];

                $user_info = User::select('id', 'first_name', 'last_name', 'photo_url')->where('id', $opponent_id)->get()->first();

                $following = UserConnections::where('follow_user_id', $opponent_id)
                                ->where('user_id', \Auth::user()->id)->exists();

                $follow = UserConnections::where('user_id', $opponent_id)
                                ->where('follow_user_id', \Auth::user()->id)->exists();

                $point = Leaderboard::select('punches_count')->where('user_id', $opponent_id)->get()->first();

                $points = (!empty($point['punches_count'])) ? $point['punches_count'] : 0;
                $chat[$chat_count]['opponent_user'] = [
                    'id' => $user_info['id'],
                    'first_name' => $user_info['first_name'],
                    'last_name' => $user_info['last_name'],
                    'photo_url' => $user_info['photo_url'],
                    'points' => (int) $points['punches_count'],
                    'user_following' => (bool) $following,
                    'user_follower' => (bool) $follow
                ];

                $chat[$chat_count]['msg_time'] = strtotime($chat_msg['msg_time']);
                $chat[$chat_count]['lst_msg'] = $chat_msg['message'];
                $chat[$chat_count]['unread_msg_count'] = ChatMessages::where('chat_id', $data['id'])
                        ->where('read_flag', 0)
                        ->where('user_id', '!=', $user_id)
                        ->count('message');

                $chat_count++;
            }
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $chat]);
    }

    public function getChatid($connection_id)
    {
        $user_id = \Auth::user()->id;
        $chat_detail = array();
        $existing_chat_id = Chats::select('id')
                        ->where(function ($query) use ($user_id, $connection_id) {
                            $query->where('user_one', $user_id)->where('user_two', $connection_id);
                        })
                        ->orwhere(function ($query) use ($user_id, $connection_id) {
                            $query->where('user_one', $connection_id)->where('user_two', $user_id);
                        })
                        ->get()->first();

        if (!empty($existing_chat_id->id)) {
            return $existing_chat_id->id;
        } 
        return Chats::create([
                'user_one' => $user_id,
                'user_two' => $connection_id,
            ])->id;
   }

}
