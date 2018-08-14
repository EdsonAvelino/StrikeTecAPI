<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Chat;
use App\ChatMessages;
use App\User;
use App\Helpers\Push;
use App\Helpers\PushTypes;
use Auth;

class ChatController extends Controller
{

    /**
     * @api POST /chat/send
     * 
     * Send new message
     * 
     * @param Request $request
     *
     * @return json
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
        try {
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
            
        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }    
    }
    
    /**
     * @api POST /chat/read 
     * 
     * Read messages
     * 
     * @param Request $request
     *
     * @return json
     */
    public function readMessage(Request $request)
    {
        $messageId = $request->message_id;
        $userId = Auth::user()->id;

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
     * @api GET /chat/history 
     * 
     * Get all the messages of chat
     * 
     * @param Request $request
     *
     * @return json
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
     * @api GET /chat 
     * 
     * Get all the chats 
     * 
     * @param Request $request
     *
     * @return json
     */
    public function chats(Request $request)
    {

        try {

            $userId = Auth::user()->id;
        
            $offset = (int) ($request->get('start') ? $request->get('start') : 0);
            $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

            $chatList = Chat::select('user_one', 'user_two', 'id')
                            ->where('user_one', $userId)
                            ->orwhere('user_two', $userId)
                            ->orderBy('created_at', 'desc')
                            ->offset($offset)
                            ->limit($limit)
                            ->get();

            $chatCount = 0;
            $chat = [];

            foreach ($chatList as $data) {

                $chatMsg = ChatMessages::select('message', 'created_at as msg_time')
                                        ->where('chat_id', $data['id'])
                                        ->orderBy('chat_messages.created_at', 'desc')
                                        ->offset(0)->limit(1)->get()->first();

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

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }
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
