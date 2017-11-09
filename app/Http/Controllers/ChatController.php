<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Chats;
use App\ChatMessages;
use App\User;
use App\UserConnections;
use App\Leaderboard;

class ChatController extends Controller
{

    /**
     * @api {post} /chat/send send message
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
     *           "message": "test message",
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
        $connection_id = $request->user_id;
        $message = $request->message;
        $chat_id = $this->getChatid($connection_id);
        ChatMessages::create([
            'user_id' => $sender_id,
            'read_flag' => FALSE,
            'message' => $message,
            'chat_id' => $chat_id
        ]);
        return response()->json(['error' => 'false', 'message' => '', 'data' => array('message' => $message)]);
    }

    /**
     * @api {post} /chat/read read messages
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
     *      "data": "{
     *           "message_id": "6"
     *        }"
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function ReadMessage(Request $request)
    {
        $message_id = $request->message_id;
        $user_id = \Auth::user()->id;
        ChatMessages::where('id', $message_id)->where('user_id', '!=', $user_id)->update(['read_flag' => 1]);
        return response()->json(['error' => 'false', 'message' => "Read.", 'data' => ['message_id' => $message_id]]);
    }

    /**
     * @api {get} /chat/history/{connection_id} all the messages of chat 
     * @apiGroup Chat
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} connection_id Connection ID
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "connection_id": 6,
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
    public function chatHistory($connection_id, Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $chat_id = $this->getChatid($connection_id);
        $chat_detail = ChatMessages::select('chat_messages.id as message_id', 'user_id as sender_id', 'read_flag as read', 'chat_id', 'message', 'chat_messages.created_at as send_time')
                        ->join('users', 'users.id', '=', 'chat_messages.user_id')
                        ->orderBy('chat_messages.created_at', 'desc')
                        ->where('chat_id', $chat_id)
                        ->offset($offset)->limit($limit)->get();
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
     * @api {get} /chat all the chats 
     * @apiGroup Chat
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
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
     *             "msg_time": "2017-11-06 17:44:24",
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
     *             "msg_time": "2017-11-06 11:28:22",
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
        $i = 0;
        $chat = array();
        foreach ($chat_list as $data) {
            $opponent_id = ($data['user_one'] != $user_id) ? $data['user_one'] : $data['user_two'];

            $user_info = User::select('id', 'first_name', 'last_name', 'photo_url')->where('id', $opponent_id)->get()->first();

            $following = UserConnections::where('follow_user_id', $opponent_id)
                            ->where('user_id', \Auth::user()->id)->exists();

            $follow = UserConnections::where('user_id', $opponent_id)
                            ->where('follow_user_id', \Auth::user()->id)->exists();

            $point = Leaderboard::select('punches_count')->where('user_id', $opponent_id)->get()->first();

            $points = (!empty($point['punches_count'])) ? $point['punches_count'] : 0;
            $chat[$i]['opponent_user'] = [
                'id' => $user_info['id'],
                'first_name' => $user_info['first_name'],
                'last_name' => $user_info['last_name'],
                'photo_url' => $user_info['photo_url'],
                'points' => (int) $points['punches_count'],
                'user_following' => (bool) $following,
                'user_follower' => (bool) $follow
            ];
            $chat_msg = ChatMessages::select('message', 'created_at as msg_time')
                            ->where('chat_id', $data['id'])
                            ->orderBy('chat_messages.created_at', 'desc')
                            ->offset(0)->limit(1)->get()->first();
            $chat[$i]['msg_time'] = strtotime($chat_msg['msg_time']);
            $chat[$i]['lst_msg'] = $chat_msg['message'];
            $chat[$i]['unread_msg_count'] = ChatMessages::where('chat_id', $data['id'])
                    ->where('read_flag', 0)
                    ->where('user_id', '!=', $user_id)
                    ->count('message');

            $i++;
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
            $chat_id = $existing_chat_id->id;
        } else {
            Chats::create([
                'user_one' => $user_id,
                'user_two' => $connection_id,
            ]);
            $chat = Chats::select('id')
                            ->where(function ($query) use ($user_id, $connection_id) {
                                $query->where('user_one', $user_id)->where('user_two', $connection_id);
                            })
                            ->orwhere(function ($query) use ($user_id, $connection_id) {
                                $query->where('user_one', $connection_id)->where('user_two', $user_id);
                            })
                            ->get()->first();
            $chat_id = $chat->id;
        }
        return $chat_id;
    }

}
