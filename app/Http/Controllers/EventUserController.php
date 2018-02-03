<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\EventUser;
use App\User;

class EventUserController extends Controller
{
    /**
     * @api {post} /fan/event/users/add Register user to event
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id id of event
     * @apiParam {int} user_id list of user ID
     * @apiParamExample {json} Input
     *    {
     *      "event_id": "2",
     *      "user_id": "1,2,3",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event create successfully
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *       "error": "false",
     *       "message": "User has been added successfully",
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function usersAddEvent(Request $request)
    {
        $data = $request->input();
        $userIds = explode(',', $data['user_id']);

        foreach ($userIds as $userId) {
            if ($userId) {
                EventUser::updateOrCreate(['user_id' => $userId, 'event_id' => $data['event_id']], ['status' => 1]);
            }
        }
        return response()->json(['error' => 'false', 'message' => 'User has been added successfully']);
    }

    /**
     * @api {post} /fan/event/users/remove remove users from event
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id id of event
     * @apiParam {int} user_id id of user
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 1,
     *      "user_id": 1,2,3
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Users has been removed from event successfully",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function eventUsersRemove(Request $request)
    {
        $validator = Validator::make($request->all(), [
                    'event_id' => 'required|exists:event_users',
                    'user_id' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors]);
        }
        try {
            $data = $request->input();
            $userIds = explode(',', $data['user_id']);

            foreach ($userIds as $userId) {
                if ($userId) {
                    $eventId = $request->get('event_id');
                    EventUser::where('event_id', $eventId)
                            ->where('user_id', $userId)->delete();
                }
            }
            return response()->json([
                        'error' => 'false',
                        'message' => 'Users has been removed from event successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }
}
