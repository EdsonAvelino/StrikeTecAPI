<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Chat;
use App\ChatMessages;
use App\User;
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

        $validator = \Validator::make($request->all(), [
            'user_id' => 'required',
            'message' => 'required|min:2',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors]);

        }


            $senderId = \Auth::user()->id;
        $userId = $request->user_id;
        $message = $request->message;

        $chatId = $this->getChatid($userId);

        $chatId = ChatMessages::create([
                    'user_id' => $senderId,
                    'read_flag' => false,
                    'message' => $message,
                    'chat_id' => $chatId
                ])->id;

        $chatResponse = ChatMessages::where('id', $chatId)
                ->select('id as message_id', 'user_id as sender_id', 'message', 'read_flag as read', 'created_at as send_time')
                ->first();

        $chatResponse->read = filter_var($chatResponse->read, FILTER_VALIDATE_BOOLEAN);
        $chatResponse->send_time = strtotime($chatResponse->send_time);

        $senderUser = User::get($senderId);

        $pushMessage = 'You received new message from ' . $senderUser->first_name . ' ' . $senderUser->last_name;

        Push::send(PushTypes::CHAT_SEND_MESSAGE, $userId, $senderId, $pushMessage, ['message' => $chatResponse]);

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
        $messageId = $request->message_id;
        $userId = \Auth::user()->id;

        $chatMessage = ChatMessages::where('id', $messageId)->where('user_id', '!=', $userId)->first();
        if ($chatMessage)
        {

            $chatMessage->update(['read_flag' => 1]);
            if ($chatMessage->user_id != \Auth::user()->id) {

                $pushMessage = 'Read message';

                $chatResponse = ChatMessages::where('id', $messageId)
                    ->select('id as message_id', 'user_id as sender_id', 'message', 'read_flag as read', 'created_at as send_time')->first();

                $chatResponse->read = filter_var($chatResponse->read, FILTER_VALIDATE_BOOLEAN);
                $chatResponse->send_time = strtotime($chatResponse->send_time);

                Push::send(PushTypes::CHAT_READ_MESSAGE, $chatMessage->user_id, $userId, $pushMessage, ['message' => $chatResponse]);
            }

            return response()->json(['error' => 'false', 'message' => "Read.", 'data' => ['message_id' => $messageId]]);
        }else{
            return response()->json(['error' => 'true', 'message' => "Message not found"]);

        }

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
        $offsetMessageIid = (int) ($request->get('message_id') ? $request->get('message_id') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $connectionId = (int) $request->get('user_id');
        $chatId = $this->getChatid($connectionId);
        $chatDetail = ChatMessages::select('chat_messages.id as message_id', 'user_id as sender_id', 'read_flag as read', 'chat_id', 'message', 'chat_messages.created_at as send_time')
                        ->join('users', 'users.id', '=', 'chat_messages.user_id')->where('chat_id', $chatId)
                        ->where(function($query) use ($offsetMessageIid) {
                            if ($offsetMessageIid === -1) {
                                $query->where('chat_messages.id', '>=', $offsetMessageIid);
                            } else {
                                $query->where('chat_messages.id', '<=', $offsetMessageIid);
                            }
                        })->orderBy('chat_messages.created_at', 'desc')->limit($limit)->get();

        $chat = array();
        foreach ($chatDetail as $chatDetails) {
            $chat[] = ['message_id' => $chatDetails['message_id'],
                'sender_id' => $chatDetails['sender_id'],
                'read' => (bool) $chatDetails['read'],
                'message' => $chatDetails['message'],
                'send_time' => strtotime($chatDetails['send_time'])];
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
        $userId = \Auth::user()->id;
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $chatList = Chat::select('user_one', 'user_two', 'id')
                        ->where('user_one', $userId)->orwhere('user_two', $userId)->orderBy('created_at', 'desc')->offset($offset)->limit($limit)->get()->all();
        $chatCount = 0;
        $chat = array();
        foreach ($chatList as $data) {
            $chatMsg = ChatMessages::select('message', 'created_at as msg_time')->where('chat_id', $data['id'])
                            ->orderBy('chat_messages.created_at', 'desc')->offset(0)->limit(1)->get()->first();
            if ($chatMsg) {
                $opponentId = ($data['user_one'] != $userId) ? $data['user_one'] : $data['user_two'];
                $chat[$chatCount]['opponent_user'] = User::get($opponentId);
                $chat[$chatCount]['msg_time'] = strtotime($chatMsg['msg_time']);
                $chat[$chatCount]['lst_msg'] = $chatMsg['message'];
                $chat[$chatCount]['unread_msg_count'] = ChatMessages::where('chat_id', $data['id'])
                        ->where('read_flag', 0)
                        ->where('user_id', '!=', $userId)
                        ->count('message');
                $chatCount++;
            }
        }
        return response()->json(['error' => 'false', 'message' => '', 'data' => $chat]);
    }

    public function getChatid($connectionId)
    {
        $userId = \Auth::user()->id;
        $existingChatId = Chat::select('id')
                        ->where(function ($query) use ($userId, $connectionId) {
                            $query->where('user_one', $userId)->where('user_two', $connectionId);
                        })
                        ->orwhere(function ($query) use ($userId, $connectionId) {
                            $query->where('user_one', $connectionId)->where('user_two', $userId);
                        })
                        ->get()->first();

        if (!empty($existingChatId->id)) {
            return $existingChatId->id;
        }
        return Chat::create([
                    'user_one' => $userId,
                    'user_two' => $connectionId,
                ])->id;
    }

}
