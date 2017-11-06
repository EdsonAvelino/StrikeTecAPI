<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Chats;
use App\ChatMessages;
use App\User;

class ChatController extends Controller
{

    /**
     * @api {post} /chat/create/{connection_id}  create connection between user to send message
     * @apiGroup Chat
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} connection_id  user id to create chat
     * @apiParamExample {json} Input
     *    {
     *     'connection_id':1
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data list of sent request battles
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": {
     *           "chat_id": 7,
     *           "connection_id": "1"
     *           "messages": [
     *           {
     *             "id": 24,
     *             "user_id": 12,
     *             "first_name": "Anchal",
     *             "last_name": "Gupta",
     *             "photo_url": null,
     *             "message": "testst",
     *             "send_time": "2017-11-06 11:22:38"
     *         },
     *         {
     *             "id": 23,
     *             "user_id": 7,
     *             "first_name": "Qiang",
     *             "last_name": "Hu",
     *             "photo_url": null,
     *             "message": "testst",
     *             "send_time": "2017-11-06 11:22:35"
     *         },
     *         {
     *             "id": 16,
     *             "user_id": 12,
     *             "first_name": "Anchal",
     *             "last_name": "Gupta",
     *             "photo_url": null,
     *             "message": "yeshghgg",
     *             "send_time": "2017-11-06 11:18:27"
     *         }
     *    ]
     *       }
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function createChatSession(Request $request)
    {
        $user_id = \Auth::user()->id;
        $connection_id = $request->connection_id;
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
            $chat_detail = ChatMessages::select('chat_messages.id', 'user_id', 'first_name', 'last_name', 'photo_url', 'message', 'chat_messages.created_at as send_time')
                            ->join('users', 'users.id', '=', 'chat_messages.user_id')
                            ->orderBy('chat_messages.created_at', 'desc')
                            ->where('chat_id', $chat_id)
                            ->offset(0)->limit(20)->get();
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
        return response()->json(['error' => 'false', 'message' => '', 'data' => array('chat_id' => $chat_id, 'connection_id' => $connection_id, 'messages' => $chat_detail)]);
    }

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
     * @apiParam {Number} chat_id Chat ID
     * @apiParam {String} message message to send user 
     * @apiParamExample {json} Input
     *    {
     *      "chat_id": 1,
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
     *           "read_flag": false
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
        $chat_id = $request->chat_id;
        $message = $request->message;
        ChatMessages::create([
            'user_id' => $sender_id,
            'read_flag' => FALSE,
            'message' => $message,
            'chat_id' => $chat_id
        ]);
        return response()->json(['error' => 'false', 'message' => '', 'data' => array('message' => $message, 'read_flag' => FALSE)]);
    }

    /**
     * @api {get} /chat/receive/{chat_id} receive messages
     * @apiGroup Chat
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} chat_id Chat ID
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "chat_id": 20,
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
     *                {
     *              "id": 24,
     *              "user_id": 33,
     *              "first_name": "Anchal",
     *              "last_name": "Gupta",
     *              "photo_url": null,
     *              "chat_id": 6,
     *              "message": "testst",
     *              "read_flag": 0,
     *              "send_time": "2017-11-04 14:13:27"
     *          },
     *          {
     *              "id": 23,
     *              "user_id": 33,
     *              "first_name": "Anchal",
     *              "last_name": "Gupta",
     *              "photo_url": null,
     *              "chat_id": 6,
     *              "message": "testst",
     *              "read_flag": 0,
     *              "send_time": "2017-11-04 14:12:45"
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
    public function receiveMessage($chat_id, Request $request)
    {
        $user_id = \Auth::user()->id;
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $chat_detail = ChatMessages::select('chat_messages.id', 'user_id', 'first_name', 'last_name', 'photo_url', 'chat_id', 'message', 'read_flag', 'chat_messages.created_at as send_time')
                        ->join('users', 'users.id', '=', 'chat_messages.user_id')
                        ->orderBy('chat_messages.created_at', 'desc')
                        ->where('chat_id', $chat_id)
                        ->where('read_flag', false)
                        ->where('chat_messages.user_id', '!=', $user_id)
                        ->offset($offset)->limit($limit)->get();
        ChatMessages::where('chat_id', $chat_id)->where('user_id', '!=', $user_id)->update(['read_flag' => 1]);
        return response()->json(['error' => 'false', 'message' => '', 'data' => $chat_detail]);
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
     * @apiParam {Number} chat_id Chat ID
     * @apiParamExample {json} Input
     *    {
     *      "chat_id": 20,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Read.",
     *      "data": ""
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
        $chat_id = $request->chat_id;
        $user_id = \Auth::user()->id;
        ChatMessages::where('chat_id', $chat_id)->where('user_id', '!=', $user_id)->update(['read_flag' => 1]);
        return response()->json(['error' => 'false', 'message' => "Read.", 'data' => '']);
    }

    /**
     * @api {get} /chat/history/{chat_id} all the messages of chat 
     * @apiGroup Chat
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} chat_id Chat ID
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "chat_id": 6,
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
     *             "id": 24,
     *             "user_id": 12,
     *             "first_name": "Anchal",
     *             "last_name": "Gupta",
     *             "photo_url": null,
     *             "chat_id": 6,
     *             "message": "testst",
     *             "send_time": "2017-11-06 11:22:38"
     *         },
     *         {
     *             "id": 23,
     *             "user_id": 7,
     *             "first_name": "Qiang",
     *             "last_name": "Hu",
     *             "photo_url": null,
     *             "chat_id": 6,
     *             "message": "testst",
     *             "send_time": "2017-11-06 11:22:35"
     *         },
     *         {
     *             "id": 16,
     *             "user_id": 12,
     *             "first_name": "Anchal",
     *             "last_name": "Gupta",
     *             "photo_url": null,
     *             "chat_id": 6,
     *             "message": "yeshghgg",
     *             "send_time": "2017-11-06 11:18:27"
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
    public function chatHistory($chat_id, Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $chat_detail = ChatMessages::select('chat_messages.id', 'user_id', 'first_name', 'last_name', 'photo_url', 'chat_id', 'message', 'chat_messages.created_at as send_time')
                        ->join('users', 'users.id', '=', 'chat_messages.user_id')
                        ->orderBy('chat_messages.created_at', 'desc')
                        ->where('chat_id', $chat_id)
                        ->offset($offset)->limit($limit)->get();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $chat_detail]);
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
     *           {
     *              "chat_id": 1,
     *              "chat_with": "Anchal  Gupta",
     *              "photo_url": null,
     *              "msg_time": "2017-11-06 11:14:29",
     *              "lst_msg": "yeshghgg",
     *              "unread_msg_count": 3
     *          },
     *          {
     *              "chat_id": 4,
     *              "chat_with": "Anchal Gupta",
     *              "photo_url": null,
     *              "msg_time": "2017-11-06 11:28:22",
     *              "lst_msg": "yeshghgg",
     *              "unread_msg_count": 0
     *          },
     *          {
     *              "chat_id": 6,
     *              "chat_with": "test",
     *              "photo_url": null,
     *              "msg_time": "2017-11-06 11:22:38",
     *              "lst_msg": "testst",
     *              "unread_msg_count": 0
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
    public function chats(Request $request)
    {
        $user_id = \Auth::user()->id;
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $chat_list = Chats::select('user_one', 'user_two', 'id')
                        ->where('user_one', $user_id)
                        ->orwhere('user_two', $user_id)
                        ->offset($offset)->limit($limit)->get()->all();
        $i = 0;
        $chat = array();
        foreach ($chat_list as $data) {
            $opponent_id = ($data['user_one'] != $user_id) ? $data['user_one'] : $data['user_two'];

            $user_info = User::select('id', 'first_name', 'last_name', 'photo_url')->where('id', $opponent_id)->get()->first();
            $chat[$i]['chat_id'] = $data['id'];
            $chat[$i]['chat_with'] = $user_info['first_name'] . " " . $user_info['last_name'];
            $chat[$i]['photo_url'] = $user_info['photo_url'];
            $chat_msg = ChatMessages::select('message', 'created_at as msg_time')
                            ->where('chat_id', $data['id'])
                            ->orderBy('chat_messages.created_at', 'desc')
                            ->offset(0)->limit(1)->get()->first();
            $chat[$i]['msg_time'] = $chat_msg['msg_time'];
            $chat[$i]['lst_msg'] = $chat_msg['message'];
            $chat[$i]['unread_msg_count'] = ChatMessages::where('chat_id', $data['id'])
                    ->where('read_flag', 0)
                    ->where('user_id', '!=', $user_id)
                    ->count('message');

            $i++;
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $chat]);
    }

}
