<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\EventFanActivity;
use Validator;
use DB;

Class EventFanActivityController extends Controller {
    /**
     * @api {post} fan/event/activity/add add activity to event
     * @apiGroup event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id id of event
     * @apiParam {int} activity_id  id of activity
     * @apiParamExample {json} Input
     *    {
     *      "event_id": "2",
     *      "activity_id": "1",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *       "error": "false",
     *       "message": "Activity has been added successfully",
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function activityAddEvent(Request $request)
    {     
        try {
            $eventActivityInfo = EventFanActivity::where('event_id', $request->get('event_id'))
                        ->where('activity_id', $request->get('activity_id'))->get();
            if(count($eventActivityInfo) <= 0){
                EventFanActivity::Create([
                    'event_id' => $request->get('event_id'),
                    'activity_id' => $request->get('activity_id')
                ]);
                return response()->json([ 'error' => 'false', 'message' => 'Activity has been added successfully']);
            }
          return response()->json([ 'error' => 'false', 'message' => 'Activity already added for this event']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'true', 'message' => $e->getMessage()]);
        }
    } 
    
     /**
     * @api {post} /fan/activity/remove remove activity
     * @apiGroup event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} id id of event
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 1,
     *      "activity_id":2
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Activity has been removed for event successfully",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function activityRemove(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'activity_id'    => 'required|exists:event_fan_activities',
        ]);
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('id')]);
        }
        try {
            $activityID = $request->get('activity_id');
            $eventID = $request->get('event_id');
            DB::beginTransaction();
            EventFanActivity::where('activity_id', $activityID)
                            ->where('event_id', $eventID)->delete();
            DB::commit();
            return response()->json([
                'error' => 'false',
                'message' => 'Activity has been removed for event successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                    'error' => 'true',
                    'message' => 'Invalid request',
            ]);
        }
    }
}