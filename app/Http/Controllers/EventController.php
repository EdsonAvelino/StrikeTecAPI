<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;
use App\EventUser;
use App\FanActivity;
use App\EventFanActivity;
use App\EventSession;
use Validator;
use DB;

class EventController extends Controller
{
    /**
     * @api {post} /fan/event register event details
     * @apiGroup Event
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
     * @apiParam {int} activity_id id of activity
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
     *      "activity_id": "1",
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
     *       "message": "Event has been created successfully",
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
                    $event->save();
                    if(!empty($request->get('activity_id'))) {
                        $request->merge(['event_id' => $request->get('event_id'), 'activity_id' => $request->get('activity_id')]);
                        $objEventFanActivity = new EventFanActivityController();
                        $objEventFanActivity->activityAddEvent($request);
                    }
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
                        'user_id' =>  \Auth::user()->id,
                        'location_id' => (int) $request->get('location_id'),
                        'company_id' => $company_id,
                        'description' => !empty($request->get('description')) ? $request->get('description') : '',
                        'to_date' => date('Y-m-d', strtotime($request->get('to_date'))),
                        'to_time' => $request->get('to_time'),
                        'from_date' => date('Y-m-d', strtotime($request->get('from_date'))),
                        'from_time' => $request->get('from_time'),
                        'all_day' => $request->get('all_day'),
                    ])->id;
            if(!empty($request->get('activity_id'))) {
                $request->merge(['event_id' => $event_detail, 'activity_id' => $request->get('activity_id')]);
                $objEventFanActivity = new EventFanActivityController();
                $objEventFanActivity->activityAddEvent($request);
            }
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
     * @api {get} /fan/events get event details information
     * @apiGroup Event
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
     * @apiGroup Event
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
     *               "photo_url": "null",
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
    function userEventList() {
        try {
            $eventStorage = array();
            $eventInfo = array();
            $company_id = \Auth::user()->company_id;
            $ObjEvent = new Event();
            $eventList = $ObjEvent->usersList($company_id);
            foreach($eventList  as $val) {  
                $ObjEventUser = new EventUser();
                $eventInfo = $ObjEventUser->getUsersList($val->user_id);
                if(!empty($val->events)){
                    $eventInfo->events =  array_map('intval', explode(',', $val->events));
                }
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
    
     /**
     * @api {get} /fan/my/events get my events details information
     * @apiGroup Event
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
     *       "message": "Events list information",
     *       "data": [
     *           {
     *               "id": 1,
     *               "user_id": 1,
     *               "company_id": 1,
     *               "event_title": "yearly tournament edit",
     *               "location_id": 2,
     *               "description": "hii this is descripiton",
     *               "to_date": "2018-12-12",
     *               "to_time": "20:00",
     *               "from_date": "2018-12-12",
     *               "from_time": "20:45",
     *               "all_day": false,
     *               "type_of_activity": "power",
     *               "created_at": "2017-11-28 16:28:37",
     *               "updated_at": "2017-12-01 19:02:44",
     *               "location_name": "Manhattan, New York",
     *               "company_name": "Normal",
     *               "users": [
     *                   {
     *                       "id": 7,
     *                       "first_name": "Qiang",
     *                       "last_name": "Hu",
     *                       "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512069189.jpg",
     *                       "birthday": "1990-06-10",
     *                       "gender": "male",
     *                       "height": 57,
     *                       "weight": 200,
     *                       "email": "toniorasma@yahoo.com",
     *                       "state_name": "Texas",
     *                       "country_name": "United States",
     *                       "city_name": null
     *                   },
     *                   {
     *                       "id": 12,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "birthday": null,
     *                       "gender": null,
     *                       "height": null,
     *                       "weight": null,
     *                       "email": "anchal@gupta.com",
     *                       "state_name": null,
     *                       "country_name": null,
     *                       "city_name": null
     *                   },
     *                   {
     *                       "id": 13,
     *                       "first_name": "John",
     *                       "last_name": "Smith",
     *                       "photo_url": null,
     *                       "birthday": "1989-07-04",
     *                       "gender": "male",
     *                       "height": null,
     *                       "weight": 201,
     *                       "email": "test001@smith.com",
     *                       "state_name": null,
     *                       "country_name": null,
     *                       "city_name": null
     *                   }
     *               ]
     *           },
     *           {
     *               "id": 2,
     *               "user_id": 1,
     *               "company_id": 1,
     *               "event_title": "yearly tournament 2 edit",
     *               "location_id": 1,
     *               "description": "",
     *               "to_date": "2018-12-12",
     *               "to_time": "20:00",
     *               "from_date": "2019-12-12",
     *               "from_time": "20:45",
     *               "all_day": false,
     *               "type_of_activity": "",
     *               "created_at": "2017-11-28 16:39:44",
     *               "updated_at": "2017-12-01 19:02:48",
     *               "location_name": "Las Vegas, Nevada",
     *               "company_name": "Normal",
     *               "users": [
     *                   {
     *                       "id": 7,
     *                       "first_name": "Qiang",
     *                       "last_name": "Hu",
     *                       "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512069189.jpg",
     *                       "birthday": "1990-06-10",
     *                       "gender": "male",
     *                       "height": 57,
     *                       "weight": 200,
     *                       "email": "toniorasma@yahoo.com",
     *                       "state_name": "Texas",
     *                       "country_name": "United States",
     *                       "city_name": null
     *                   },
     *                   {
     *                       "id": 12,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "birthday": null,
     *                       "gender": null,
     *                       "height": null,
     *                       "weight": null,
     *                       "email": "anchal@gupta.com",
     *                       "state_name": null,
     *                       "country_name": null,
     *                       "city_name": null
     *                   }
     *               ]
     *           }
     *      ]
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function myEventsUsersList()
    {
        try {
            $eventStorage = array();
            $eventInfo = array();
            $userID = \Auth::user()->id;
            $ObjEvent = new Event();
            $eventList = $ObjEvent->myEventList($userID);
            foreach ($eventList as $val) {
                $eventInfo = $val;
                $eventInfo->all_day = (bool) $val->all_day;
                $eventInfo->status = (bool) $val->status;
                $ObjEventUser = new EventUser();
                $eventInfo->users = $ObjEventUser->myEventUsersInfo($val->id);
                $eventStorage[] = $eventInfo;
            }
            return response()->json(['error' => 'false', 'message' => 'My events list information', 'data' => $eventStorage]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }
    
    /**
     * @api {get} /fan/all/events get all events details information
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data All events list information
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "Event list information",
     *       "data": [
     *           {
     *               "id": 1,
     *               "user_id": 1,
     *               "company_id": 1,
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
     *               "updated_at": "2017-12-01 19:02:44",
     *               "location_name": "Manhattan, New York",
     *               "company_name": "Normal",
     *               "users": [
     *                   {
     *                       "id": 7,
     *                       "first_name": "Qiang",
     *                       "last_name": "Hu",
     *                       "photo_url": "http://172.16.11.45/storage/profileImages/sub-1509460359.png",
     *                       "birthday": "1990-06-10",
     *                       "gender": "male",
     *                       "height": 57,
     *                       "weight": 200,
     *                       "email": "toniorasma@yahoo.com",
     *                       "state_name": "Texas",
     *                       "country_name": "United States",
     *                       "city_name": null
     *                   },
     *                   {
     *                       "id": 12,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "birthday": null,
     *                       "gender": null,
     *                       "height": null,
     *                       "weight": null,
     *                       "email": "anchal@gupta.com",
     *                       "state_name": null,
     *                       "country_name": null,
     *                       "city_name": null
     *                   },
     *                   {
     *                       "id": 13,
     *                       "first_name": "John",
     *                       "last_name": "Smith",
     *                       "photo_url": null,
     *                       "birthday": "1989-07-04",
     *                       "gender": "male",
     *                       "height": null,
     *                       "weight": 201,
     *                       "email": "test001@smith.com",
     *                       "state_name": null,
     *                       "country_name": null,
     *                       "city_name": null
     *                   }
     *               ]
     *           },
     *           {
     *               "id": 2,
     *               "user_id": 1,
     *               "company_id": 1,
     *               "event_title": "yearly tournament 2 edit",
     *               "location_id": 1,
     *               "description": "",
     *               "to_date": "2018-12-12",
     *               "to_time": "20:00",
     *               "from_date": "2019-12-12",
     *               "from_time": "20:45",
     *               "all_day": 0,
     *               "type_of_activity": "",
     *               "created_at": "2017-11-28 16:39:44",
     *               "updated_at": "2017-12-01 19:02:48",
     *               "location_name": "Las Vegas, Nevada",
     *               "company_name": "Normal",
     *               "users": [
     *                   {
     *                       "id": 7,
     *                       "first_name": "Qiang",
     *                       "last_name": "Hu",
     *                       "photo_url": "http://172.16.11.45/storage/profileImages/sub-1509460359.png",
     *                       "birthday": "1990-06-10",
     *                       "gender": "male",
     *                       "height": 57,
     *                       "weight": 200,
     *                       "email": "toniorasma@yahoo.com",
     *                       "state_name": "Texas",
     *                       "country_name": "United States",
     *                       "city_name": null
     *                   },
     *                   {
     *                       "id": 12,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "birthday": null,
     *                       "gender": null,
     *                       "height": null,
     *                       "weight": null,
     *                       "email": "anchal@gupta.com",
     *                       "state_name": null,
     *                       "country_name": null,
     *                       "city_name": null
     *                   }
     *               ]
     *           }
     *      ]
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function allEventsUsersList()
    {
        try {
            $eventStorage = array();
            $eventInfo = array();
            $company_id = \Auth::user()->company_id;
            $ObjEvent = new Event();
            $eventList = $ObjEvent->eventsList($company_id);
            foreach ($eventList as $val) {
                $eventInfo = $val;
                $eventInfo->all_day = (bool) $val->all_day;
                $eventInfo->status = (bool) $val->status;
                $ObjEventUser = new EventUser();
                $eventInfo->users = $ObjEventUser->myEventUsersInfo($val->id);
                $eventStorage[] = $eventInfo;
            }
            return response()->json(['error' => 'false', 'message' => 'All events list information', 'data' => $eventStorage]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }
    
    /**
     * @api {post} /fan/event/remove remove event
     * @apiGroup Event
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
     *      "id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event create successfully
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Event has been removed successfully",
     *   }
     * }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function eventRemove(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'id'    => 'required|exists:events',
        ]);
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('id')]);
        }
        try {
            $eventID = $request->get('id');
            DB::beginTransaction();
            Event::find($eventID)->delete();
            EventUser::where('event_id', $eventID)->delete();
            DB::commit();
            return response()->json([
                'error' => 'false',
                'message' => 'Event has been removed successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                    'error' => 'true',
                    'message' => 'Invalid request',
            ]);
        }
    }
    /**
     * @api {get} /fan/event/users/activities/<event_id> get users details and activity details by event id
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   "error": "false",
     *   "message": "Event users activity details",
     *   "data": {
     *      "id": 8,
     *      "user_id": "66",
     *      "company_id": "3",
     *      "event_title": "chetu event",
     *      "location_id": "1",
     *      "description": "desc",
     *      "to_date": "2017-12-22",
     *      "to_time": "07:21:00",
     *      "from_date": "2017-12-04",
     *      "from_time": "07:21:00",
     *      "all_day": "0",
     *      "type_of_activity": "Endurance",
     *      "created_at": "2017-12-04 13:50:46",
     *      "updated_at": "2017-12-04 09:07:35",
     *       "users": [
     *           {
     *               "id": "67",
     *               "name": "a",
     *               "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512395499.jpg",
     *               "birthday": "2017-12-04",
     *               "gender": "male",
     *               "height": "96",
     *               "weight": "297",
     *               "email": "q@test.com",
     *               "state_name": null,
     *               "country_name": null,
     *               "city_name": null
     *           },
     *           {
     *               "id": "68",
     *               "name": "w",
     *               "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512395560.jpg",
     *               "birthday": "2017-12-04",
     *               "gender": "female",
     *               "height": "96",
     *               "weight": "296",
     *               "email": "w@test.com",
     *               "state_name": null,
     *               "country_name": null,
     *               "city_name": null
     *           },
     *           {
     *               "id": "69",
     *               "name": "e",
     *               "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512395662.jpg",
     *               "birthday": "2017-09-30",
     *               "gender": "female",
     *               "height": "100",
     *               "weight": "300",
     *               "email": "e@test.com",
     *               "state_name": null,
     *               "country_name": null,
     *               "city_name": null
     *           }
     *       ],
     *      "activities": [
     *       {
     *           "id": 1,
     *           "name": "Speed",
     *           "description": "Proin ut quam eros. Donecsed lobortis diam. Nulla necodio lacus.",
     *           "image_url": "http://192.168.14.253/storage/fanuser/activityicon/activity_icon_speed.png",
     *           "created_at": "2017-12-15 05:40:20",
     *           "updated_at": "2017-12-15 11:10:38"
     *           "status": "true",
     *       }
     *       {
     *           "id": 2,
     *           "name": "Power",
     *           "description": "Proin ut quam eros. Donecsed lobortis diam. Nulla necodio lacus.",
     *           "image_url": "http://192.168.14.253/storage/fanuser/activityicon/activity_icon_power.png",
     *           "created_at": "2017-12-15 05:40:20",
     *           "updated_at": "2017-12-15 11:10:38"
     *           "status": "false",
     *       }
     *       ],
     *   }
     *}
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function getUsersActivitiesInfoByEvent($event_id)
    {  
        try{
            $rules = [
                'id' => 'required|exists:events',
            ];
            $input = array('id' => $event_id);
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) { 
                $errors = $validator->errors();
                return response()->json(['error' => 'true', 'message' =>  $errors->first('id')]);
            }
            $ObjEventUser = new EventUser();
            $eventActivityInfoUsersList = Event::with('eventUser', 'eventActivity')->find($event_id)->toArray();
            //Get users list
            foreach($eventActivityInfoUsersList['event_user'] as $val) {
                
                $eventActivityInfoUsersList['users'][] = $ObjEventUser->getUsersList($val['user_id']);
            }
            //Get activities details and users information
           foreach($eventActivityInfoUsersList['event_activity'] as $data) {
                $tempStorage = FanActivity::where('id', $data['activity_id'])->first();
                $tempStoreActivityArray = EventSession::with('user')->where('activity_id', $data['activity_id'])
                                             ->where('event_id', $event_id)->get()->toArray();
           
                $tempStorage->status = $data['status'];
                $tempSessionStoreArray = array();
                //Get session users information
                foreach($tempStoreActivityArray as $userInfo){
                        $tempSessionStoreArray[] = $userInfo['user'];
                    }
                $tempStorage->sessionUsers = $tempSessionStoreArray;
                $eventActivityInfoUsersList['activities'][] = $tempStorage;
            }
            if(empty($eventActivityInfoUsersList['users'])) {
                $eventActivityInfoUsersList['users'] = NULL;
            }
            if(empty($eventActivityInfoUsersList['activities'])) {
               $eventActivityInfoUsersList['activities'] = NULL; 
            } 
            // remove eloquent object
            unset($eventActivityInfoUsersList['event_user']);
            // remove eloquent object
            unset($eventActivityInfoUsersList['event_activity']);
            return response()->json(['error' => 'false', 'message' => 'Event users activity details', 'data' => $eventActivityInfoUsersList]);
        } catch (Exception $ex) {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid request'
            ]);
        }
    }
    
    /**
     * @api {get} /fan/events/logged/user get active event details information by logged user id
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Active event list information
     * @apiSuccessExample {json} Success
     *{
     * "error": "false",
     * "message": "Active event list information",
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
     *               "created_at": "2017-11-28 16:28:37",
     *               "updated_at": "2017-11-28 16:33:23",
     *               "location_name": "delhi",
     *               "company_name": "Normal",
     *               "status": true
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
     *               "created_at": "2017-11-28 16:39:44",
     *               "updated_at": "2017-11-28 16:39:44",
     *               "location_name": "noida",
     *               "company_name": "Normal",
     *               "status": true
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
    public function getuserActiveEventsList($id = false) 
    {  
      try {
            $eventStorage = array();
            $eventInfo = array();
            $loggedUserID = \Auth::user()->id;
            $eventDetails = Event::where('user_id', $loggedUserID)
                                ->where('status', 1)->get();
            return response()->json(['error' => 'false', 'message' => 'Active Event list information', 'data' => $eventDetails]);
        } catch (Exception $e) {
           return response()->json([
                       'error' => 'true',
                       'message' => 'Invalid request',
           ]);
       }
    }
    
    /**
     * @api {post} /fan/event/activity/status Activity status update
     * @apiGroup Event
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
     *      "event_id": 74,
     *      "activity_id": 1,
     *      "status": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event create successfully
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Activity status is Inprogress",
     *   }
     * }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    function statusChangeActivity(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'activity_id'    => 'required|exists:event_fan_activities',
            'event_id'    => 'required|exists:event_fan_activities',
            'status'    => 'required',
        ]);
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors]);
        }
        try {
            $eventID = $request->get('event_id');
            $activityID = $request->get('activity_id');
            if($request->get('status') == 0) {
                $eventFanActivityStatus = EventFanActivity::where('activity_id', $activityID)
                                                        ->where('event_id', $eventID)
                                                        ->first();
                if($eventFanActivityStatus->status == 0) {
                    return response()->json([
                        'error' => 'false',
                        'message' => 'Activity already is Inprogress'
                    ]);
                }  
                EventFanActivity::where('activity_id', $activityID)
                                ->where('event_id', $eventID)
                                ->update(['status' => 0]);
                return response()->json([
                            'error' => 'false',
                            'message' => 'Activity status is Inprogress'
                ]);
            } else {
                    $eventFanActivityStatus = EventFanActivity::where('activity_id', $activityID)
                                                        ->where('event_id', $eventID)
                                                        ->first();
                    EventFanActivity::where('activity_id', $activityID)
                                ->where('event_id', $eventID)
                                ->update(['status' => 1]);
                            $leaderBoardDetails = \App\EventFanActivity::select('event_id', 'activity_id')->with(['eventSessions.user', 'eventSessions' => function($q) use ($activityID) {
                        if($activityID == 1) {
                            $q->where('activity_id', $activityID)->orderBy('max_speed', 'desc');
                        }
                        if($activityID == 2) {
                            $q->where('activity_id', $activityID)->orderBy('max_force', 'desc');
                        }
                        if($activityID == 3) {
                            $q->where('activity_id', $activityID)->orderBy('max_speed', 'desc');
                        }
                    }])
                    ->where('event_id', $eventID)
                    ->where('activity_id', $activityID)->first();
                    return response()->json([
                        'error' => 'false',
                        'message' => 'Activity status change successfully',
                        'data' => $leaderBoardDetails
                    ]);
            }
        }catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                    'error' => 'true',
                    'message' => 'Invalid request',
            ]);
        
        } 
    }
    
}
