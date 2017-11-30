<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;
use App\EventUser;

class EventController extends Controller
{
    /**
     * @api {post} /event register event details
     * @apiGroup event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} event_title event title
     * @apiParam {int} location_id id of location
     * @apiParam {String} [description] description
     * @apiParam {String} to_date To date
     * @apiParam {String} to_time To time
     * @apiParam {String} from_date From date
     * @apiParam {String} from_time From time
     * @apiParam {String} [type_of_activity] Type of activity
     * @apiParam {Boolean} [all_day] it could be 0 or 1
     * @apiParamExample {json} Input
     *    {
     *      "event_title": "annual event",
     *      "location_id": "2",
     *      "description": "",
     *      "to_date": "12-11-2017",
     *      "to_time": "20:30",
     *      "from_date": "12-12-2018",
     *      "from_time": "20:00",
     *      "type_of_activity": "",
     *      "all_day": "0",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event create successfully
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": Event has been created successfully,
     *       "data": {
     *               "id": 1
     *           }
     *       }
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function addEvent(Request $request)
    {    
         try {  
            $company_id = \Auth::user()->company_id;
            if($request->get('event_id')) {
                try {   
                    $event = Event::where('id', $request->get('event_id'))->first();
                    $event->event_title = !empty(($request->get('event_title'))) ? $request->get('event_title') : $event->event_title;
                    $event->location_id = !empty($request->get('location_id')) ? $request->get('location_id') : $event->location_id;
                    $event->description = !empty($request->get('description')) ? $request->get('description') : $event->description;
                    $event->to_date = !empty($request->get('to_date')) ? date('Y-m-d', strtotime($request->get('to_date'))) : $event->to_date;
                    $event->to_time = !empty($request->get('to_time')) ? $request->get('to_time') : $event->to_time;
                    $event->from_date = !empty($request->get('from_date')) ? date('Y-m-d', strtotime($request->get('from_date'))) : $event->from_date;
                    $event->from_time = !empty($request->get('from_time')) ? $request->get('from_time') : $event->from_time;
                    $event->all_day = !empty($request->get('all_day')) ? filter_var($request->get('all_day'), FILTER_VALIDATE_BOOLEAN) : $event->all_day;
                    $event->type_of_activity = !empty($request->get('type_of_activity')) ? $request->get('type_of_activity') : $event->type_of_activity;
                    $event->save();
                    return response()->json(['error' => 'false', 'message' => 'Event has been updated successfully', 'data' => $event]);
                } catch (Exception $e) {
                    return response()->json([
                                'error' => 'true',
                                'message' => 'Invalid request',
                    ]);
                }
            }
            $event_detail = Event::create([
                        'event_title' => $request->get('event_title'),
                        'location_id' => (int) $request->get('location_id'),
                        'company_id' => $company_id,
                        'description' => !empty($request->get('description')) ? $request->get('description') : '',
                        'to_date' => date('Y-m-d', strtotime($request->get('to_date'))),
                        'to_time' => $request->get('to_time'),
                        'from_date' => date('Y-m-d', strtotime($request->get('from_date'))),
                        'from_time' => $request->get('from_time'),
                        'all_day' => $request->get('all_day'),
                        'type_of_activity' => !empty($request->get('type_of_activity')) ? $request->get('type_of_activity') : ''
                    ])->id;
            $objEventUser = new EventUserController();
            $data = ['id' => $event_detail];
            return response()->json(['error' => 'false', 'message' => 'Event has been created successfully', 'data' => $data]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }
    
    /**
     * @api {get} /events get event details information
     * @apiGroup event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event list information
     * @apiSuccessExample {json} Success
     *{
     * "error": "false",
     * "message": "Event list information",
     * "data": [
     *           {
     *               "id": 1,
     *               "event_title": "yearly tournament edit",
     *               "location_id": 2,
     *               "description": "hii this is descripiton",
     *               "to_date": "2018-12-12",
     *               "to_time": "20:00",
     *               "from_date": "2018-12-12",
     *               "from_time": "20:45",
     *               "all_day": 0,
     *               "type_of_activity": "power",
     *               "created_at": "2017-11-28 16:28:37",
     *               "updated_at": "2017-11-28 16:33:23",
     *               "location_name": "delhi",
     *               "company_name": "Normal",
     *               "users": []
     *           },
     *           {
     *               "id": 4,
     *               "event_title": "yearly tournament 2",
     *               "location_id": 1,
     *               "description": "",
     *               "to_date": "2018-12-12",
     *               "to_time": "20:00",
     *               "from_date": "2018-12-12",
     *               "from_time": "20:45",
     *               "all_day": 0,
     *               "type_of_activity": "",
     *               "created_at": "2017-11-28 16:39:44",
     *               "updated_at": "2017-11-28 16:39:44",
     *               "location_name": "noida",
     *               "company_name": "Normal",
     *               "users": []
     *           }
     *       ]
     * }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getEventList($id = false) 
    {  
      try {
            $eventStorage = array();
            $eventInfo = array();
            $company_id = \Auth::user()->company_id;
            $ObjEvent = new Event();
            $eventList = $ObjEvent->eventList($id, $company_id);
            foreach($eventList  as $val) {   
                $eventInfo = $val;
                $ObjEventUser = new EventUser();
                $eventInfo->users = $ObjEventUser->getUsersInfo($val->id);
                $eventStorage[] = $eventInfo;
            }
            return response()->json(['error' => 'false', 'message' => 'Event list information', 'data' => $eventStorage]);
        } catch (Exception $e) {
           return response()->json([
                       'error' => 'true',
                       'message' => 'Invalid request',
           ]);
       }
    }
    
    /**
     * @api {get} /fan/users/event/list get users details information
     * @apiGroup event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event list information
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "Users list information",
     *       "data": [
     *           {
     *               "id": 1,
     *               "photo_url": null,
     *               "birthday": "1970-01-01",
     *               "gender": null,
     *               "height": null,
     *               "weight": null,
     *               "email": "ntestinfo@gmail.com",
     *               "state_name": null,
     *               "country_name": null,
     *               "city_name": null,
     *               "full_name": "Nawaz Me",
     *               "events": [
     *                   1
     *               ]
     *           },
     *           {
     *               "id": 7,
     *               "photo_url": "http://172.16.11.45/storage/profileImages/sub-1509460359.png",
     *               "birthday": "1990-06-10",
     *               "gender": "male",
     *               "height": 57,
     *               "weight": 200,
     *               "email": "toniorasma@yahoo.com",
     *               "state_name": "Texas",
     *               "country_name": "United States",
     *               "city_name": null,
     *               "full_name": "Qiang Hu",
     *               "events": [
     *                   2,
     *                   1
     *               ]
     *           },
     *           
     *       ]
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    function userEventList()
    {
        try {
            $eventStorage = array();
            $eventInfo = array();
            $company_id = \Auth::user()->compnay_id;
            $ObjEvent = new Event();
            $eventList = $ObjEvent->usersList($company_id);
            foreach ($eventList as $val) {
                $ObjEventUser = new EventUser();
                $eventInfo = $ObjEventUser->getUsersList($val->user_id);
                $eventInfo->full_name = $eventInfo->first_name . ' ' . $eventInfo->last_name;
                unset($eventInfo->first_name, $eventInfo->last_name);
                $eventInfo->events = array_map('intval', explode(',', $val->events));
                $eventStorage[] = $eventInfo;
            }
            return response()->json(['error' => 'false', 'message' => 'Users list information', 'data' => $eventStorage]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }
    
}
