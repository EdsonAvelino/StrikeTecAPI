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
     * @api {post} /fan/event create/update event
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "multipart/form-data",
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
     * @apiParam {file} [event_image] image to be uploaded
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
     *      "event_image": "img.jpeg",
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
            $eventImage = '';
            $imagePath = 'storage/fanuser/event';
            $validator = Validator::make($request->all(), [
                        'event_image' => 'mimes:jpeg,jpg,png'
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json(['error' => 'true', 'message' => $errors]);
            }
            if ($request->hasFile('event_image')) {
                $eventInput = $request->file('event_image');
                $eventInformation = $eventInput->getClientOriginalName();
                $profilePicName = pathinfo($eventInformation, PATHINFO_FILENAME);
                $profilePicEXT = pathinfo($eventInformation, PATHINFO_EXTENSION);
                $eventInformation = $profilePicName . '-' . time() . '.' . $profilePicEXT;
                $eventInput->move($imagePath, $eventInformation);
                $eventImage = $eventInformation;
            }
            if (!empty($request->get('event_id'))) {
                $event = Event::where('id', $request->get('event_id'))->first();
                $event->event_title = !empty($request->get('event_title')) ? $request->get('event_title') : $event->event_title;
                $event->location_id = !empty($request->get('location_id')) ? $request->get('location_id') : $event->location_id;
                $event->description = !empty($request->get('description')) ? $request->get('description') : $event->description;
                $event->to_date = !empty($request->get('to_date')) ? date('Y-m-d', strtotime($request->get('to_date'))) : $event->to_date;
                $event->to_time = !empty($request->get('to_time')) ? $request->get('to_time') : $event->to_time;
                $event->from_date = !empty($request->get('from_date')) ? date('Y-m-d', strtotime($request->get('from_date'))) : $event->from_date;
                $event->from_time = !empty($request->get('from_time')) ? $request->get('from_time') : $event->from_time;
                $event->all_day = !empty($request->get('all_day')) ? filter_var($request->get('all_day'), FILTER_VALIDATE_BOOLEAN) : $event->all_day;
                if (!empty($eventImage)) {
                    $url = env('APP_URL') . 'storage/fanuser/event';
                    $pathToFile = str_replace($url, storage_path(), $event->image);
                    if (file_exists($pathToFile)) {
                        unlink($pathToFile); //delete earlier image
                    }
                    $event->image = $eventImage;
                }
                $event->save();
                if ((int) $request->get('activity_id') > 0) {
                    EventFanActivity::firstOrCreate(['event_id' => $request->get('event_id'), 'activity_id' => $request->get('activity_id')]);
                }


                return response()->json(['error' => 'false', 'message' => 'Event has been updated successfully', 'data' => $event]);
            } else {
                $event_detail = Event::create([
                            'event_title' => $request->get('event_title'),
                            'user_id' => \Auth::user()->id,
                            'location_id' => (int) $request->get('location_id'),
                            'company_id' => $company_id,
                            'description' => !empty($request->get('description')) ? $request->get('description') : '',
                            'to_date' => date('Y-m-d', strtotime($request->get('to_date'))),
                            'to_time' => $request->get('to_time'),
                            'from_date' => date('Y-m-d', strtotime($request->get('from_date'))),
                            'from_time' => $request->get('from_time'),
                            'all_day' => $request->get('all_day'),
                            'image' => $eventImage
                        ])->id;
                if ((int) $request->get('activity_id') > 0) {
                    EventFanActivity::firstOrCreate(['event_id' => $event_detail, 'activity_id' => $request->get('activity_id')]);
                }
                $data = ['id' => $event_detail];
                return response()->json(['error' => 'false', 'message' => 'Event has been created successfully', 'data' => $data]);
            }
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {get} /fan/users/event Get users with events
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
     *       "message": "Users list information",
     *       "data": [
     *           {
     *                "id": 7,
     *               "first_name": "test",
     *               "last_name": "test",
     *               "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1513164799.jpg",
     *               "email": "toniorasma@yahoo.com",
     *               "events": [
     *                   1
     *               ]
     *           },
     *           {
     *               "id": 7,
     *               "first_name": "test",
     *               "last_name": "test",
     *               "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1513164799.jpg",
     *               "email": "toniorasma@yahoo.com",
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
    function userEvents()
    {
        try {
            $eventStorage = array();
            $eventInfo = array();
            $users = array();
            $company_id = \Auth::user()->company_id;
            $events = Event::select('id')->where('company_id', $company_id)->get()->toArray();
            $eventIds = array_column($events, 'id');

            $eventUsers = EventUser::select('*', DB::raw('user_id as user_events'))->whereIn('event_id', $eventIds)->groupBy('user_id')->get();

            $count = 0;
            foreach ($eventUsers as $eventUser) {
                $users[$count] = $eventUser->users;
                $users[$count]->events = $eventUser->user_events;
                $count++;
            }
            return response()->json(['error' => 'false', 'message' => 'Users list information', 'data' => $users]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {get} /fan/my/events Get my events info
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
     *               "count_users_waiting_approval": 1,
     *               "is_active": false,
     *               "finalized_at": "12/28/2017",
     *               "users": [
     *                   {
     *                       "id": 7,
     *                       "first_name": "Qiang",
     *                       "last_name": "Hu",
     *                       "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512069189.jpg",
     *                       "birthday": "1990-06-10",
     *                       "gender": "male",
     *                       "email": "toniorasma@yahoo.com"
     *                   },
     *                   {
     *                       "id": 12,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "birthday": null,
     *                       "gender": null,
     *                       "email": "anchal@gupta.com"
     *                   },
     *                   {
     *                       "id": 13,
     *                       "first_name": "John",
     *                       "last_name": "Smith",
     *                       "photo_url": null,
     *                       "birthday": "1989-07-04",
     *                       "gender": "male",
     *                       "email": "test001@smith.com"
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
     *               "is_active": false,
     *               "finalized_at": "12/28/2017",
     *               "users": [
     *                   {
     *                       "id": 7,
     *                       "first_name": "Qiang",
     *                       "last_name": "Hu",
     *                       "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512069189.jpg",
     *                       "birthday": "1990-06-10",
     *                       "gender": "male",
     *                       "email": "toniorasma@yahoo.com"
     *                   },
     *                   {
     *                       "id": 12,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "birthday": null,
     *                       "gender": null,
     *                       "email": "anchal@gupta.com"
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
            $userID = \Auth::user()->id;
            $_eventList = Event::select('*', \DB::raw('company_id as company_name'), \DB::raw('location_id as location_name'), \DB::raw('id as count_users_waiting_approval'), \DB::raw('id as is_active'), \DB::raw('id as finalized_at'))
                           ->with(['eventUser.users', 'eventUser'=> function($q){ $q->where('status', 1); }])->where('user_id', $userID)->get()->toArray();

            $eventStorage = [];
            foreach ($_eventList as $events) {
                foreach ($events['event_user'] as $val) {
                    $events['users'][] = $val['users'];
                }
                unset($events['event_user']);
                $eventStorage[] = $events;
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
     * @api {get} /fan/all/events Get all events info
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
     *               "is_active": false,
     *               "count_users_waiting_approval": 1,
     *               "finalized_at": "12/28/2017",
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
     *               "is_active": false,
     *               "finalized_at": "12/28/2017",
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
            $_eventList = Event::select('*', \DB::raw('company_id as company_name'), \DB::raw('id as count_users_waiting_approval'), \DB::raw('location_id as location_name'), \DB::raw('id as is_active'), \DB::raw('id as finalized_at'))
                            ->with(['eventUser.users', 'eventUser'=> function($q){ $q->where('status', 1); }])->where('company_id', \Auth::user()->company_id)->get()->toArray();
            $eventStorage = [];
            foreach ($_eventList as $events) {
                foreach ($events['event_user'] as $val) {
                    $events['users'][] = $val['users'];
                }
                unset($events['event_user']);
                $eventStorage[] = $events;
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
     * @api {post} /fan/event/remove Remove event
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
                    'id' => 'required|exists:events',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors->first('id')]);
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
     * @api {get} /fan/event/users/activities/<event_id> Get event users, activities and session info
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
     *      "count_users_waiting_approval": 1,
     *      "created_at": "2017-12-04 13:50:46",
     *      "updated_at": "2017-12-04 09:07:35",
     *       "users": [
     *           {
     *               "id": "67",
     *               "first_name": "jk3",
     *               "last_name": null,
     *               "name": "a",
     *               "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512395499.jpg",
     *               "birthday": "2017-12-04",
     *               "email": "q@test.com",
     *           },
     *           {
     *               "id": "68",
     *               "first_name": "jk3",
     *               "last_name": null,
     *               "name": "w",
     *               "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512395560.jpg",
     *               "birthday": "2017-12-04",
     *               "gender": "female",
     *               "email": "w@test.com",
     *           },
     *           {
     *               "id": "69",
     *               "first_name": "jk3",
     *               "last_name": null,
     *               "name": "e",
     *               "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512395662.jpg",
     *               "birthday": "2017-09-30",
     *               "gender": "female",
     *               "email": "e@test.com",
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
     *           "status": true,
     *           "concluded_at":"12/28/2017"
     *           "sessionUsers": [
     *               {
     *                   "id": 149,
     *                   "first_name": "jk3",
     *                   "last_name": null,
     *                   "name": "jk3 ",
     *                   "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1513786995.jpg"
     *               },
     *               {
     *                   "id": 145,
     *                   "first_name": "jiii",
     *                   "last_name": null,
     *                   "name": "jiii ",
     *                   "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1513779707.jpg"
     *               },
     *          ],
     *       }
     *       {
     *           "id": 2,
     *           "name": "Power",
     *           "description": "Proin ut quam eros. Donecsed lobortis diam. Nulla necodio lacus.",
     *           "image_url": "http://192.168.14.253/storage/fanuser/activityicon/activity_icon_power.png",
     *           "created_at": "2017-12-15 05:40:20",
     *           "updated_at": "2017-12-15 11:10:38"
     *           "status": "false",
     *           "concluded_at":"12/28/2017"
     *       }
     *       ],
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
    public function getUsersActivitiesInfoByEvent($event_id)
    {
        try {
            $rules = [
                'id' => 'required|exists:events',
            ];
            $input = array('id' => $event_id);
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json(['error' => 'true', 'message' => $errors->first('id')]);
            }
            $event = Event::select('*', \DB::raw('company_id as company_name'), \DB::raw('id as count_users_waiting_approval'), \DB::raw('location_id as location_name'), \DB::raw('id as is_active'), \DB::raw('id as finalized_at'))
                           ->with(['eventUser.users', 'eventUser'=> function($q){ $q->where('status', 1); }, 'eventActivity.activity.eventSessions' => function($query) use($event_id) {
                            $query->where('event_id', $event_id);
                        }])->find($event_id)->toArray(); //return $event;
            //Get users list
            foreach ($event['event_user'] as $val) {
                $event['users'][] = $val['users'];
            }
            unset($event['event_user']);
            //Get activities details and users information
            foreach ($event['event_activity'] as $activities) {
                //Get session users information
                $activities['activity']['status'] = $activities['status'];
                $activities['activity']['concluded_at'] = $activities['concluded_at'];

                foreach ($activities['activity']['event_sessions'] as $sessions) {
                    if ($sessions['participant_id'] > 0) {
                        $activities['activity']['sessionUsers'][] = \App\User::select('id', 'first_name', 'last_name', \DB::raw("CONCAT(COALESCE(`first_name`, ''), ' ',COALESCE(`last_name`, '')) as name"), 'photo_url')
                                ->find($sessions['participant_id']);
                    }
                }
                unset($activities['activity']['event_sessions']);
                $event['activities'][] = $activities['activity'];
            }
            unset($event['event_activity']);
            return response()->json(['error' => 'false', 'message' => 'Event users activity details', 'data' => $event]);
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
     * {
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
                    'activity_id' => 'required|exists:event_fan_activities',
                    'event_id' => 'required|exists:event_fan_activities',
                    'status' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors]);
        }
        try {
            $eventID = $request->get('event_id');
            $activityID = $request->get('activity_id');
            if ($request->get('status') == 0) {
                $eventFanActivityStatus = EventFanActivity::where('activity_id', $activityID)
                        ->where('event_id', $eventID)
                        ->first();
                if ($eventFanActivityStatus->status == 0) {
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
                EventFanActivity::where('activity_id', $activityID)
                        ->where('event_id', $eventID)
                        ->update(['status' => 1, 'concluded_at' => date('Y-m-d H:i:s')]);
                $leaderBoardDetails = \App\EventFanActivity::select('event_id', 'activity_id')->with(['eventSessions.user', 'eventSessions' => function($q) use ($activityID) {
                                        if ($activityID == 1) {
                                            $q->where('activity_id', $activityID)->orderBy('max_speed', 'desc');
                                        }
                                        if ($activityID == 2) {
                                            $q->where('activity_id', $activityID)->orderBy('max_force', 'desc');
                                        }
                                        if ($activityID == 3) {
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
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {get} /fan/event/pending/users/<event_id> Get event pending users info
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
     *       "message": "Events pending users list information",
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
     *               "count_users_waiting_approval": 3,
     *               "is_active": false,
     *               "finalized_at": "12/28/2017",
     *               "users": [
     *                   {
     *                       "id": 7,
     *                       "first_name": "Qiang",
     *                       "last_name": "Hu",
     *                       "photo_url": "http://192.168.14.253/storage/fanuser/profilepic/user_pic-1512069189.jpg",
     *                       "birthday": "1990-06-10",
     *                       "gender": "male",
     *                       "email": "toniorasma@yahoo.com"
     *                   },
     *                   {
     *                       "id": 12,
     *                       "first_name": "Anchal",
     *                       "last_name": "Gupta",
     *                       "photo_url": null,
     *                       "birthday": null,
     *                       "gender": null,
     *                       "email": "anchal@gupta.com"
     *                   },
     *                   {
     *                       "id": 13,
     *                       "first_name": "John",
     *                       "last_name": "Smith",
     *                       "photo_url": null,
     *                       "birthday": "1989-07-04",
     *                       "gender": "male",
     *                       "email": "test001@smith.com"
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
    public function eventPendingUsersList($eventID)
    {
        try {
            $_event = Event::select('*', \DB::raw('company_id as company_name'), \DB::raw('location_id as location_name'), \DB::raw('id as count_users_waiting_approval'), \DB::raw('id as is_active'), \DB::raw('id as finalized_at'))
                            ->with(['eventUser.users', 'eventUser' => function($q) use ($eventID) {
                                $q->where('status', 0);
                                $q->where('is_cancelled', 0);
                                $q->where('event_id', $eventID);
                            }])->where('id', $eventID)->get()->toArray();
            $eventStorage = [];
            foreach ($_event as $events) {
                $events['users'] = NULL;
                foreach ($events['event_user'] as $val) {
                    $events['users'][] = $val['users'];
                }
                unset($events['event_user']);
                $eventStorage = $events;
            }
            return response()->json(['error' => 'false', 'message' => 'Events pending users list information', 'data' => $eventStorage]);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }
    
    /**
     * @api {post} /fan/event/users/status users accept or decline
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "multipart/form-data",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id id of event
     * @apiParam {String} user_id ids of user with comma separate like 1,2 
     * @apiParam {int} is_accept 0 for approve 1 for cancel
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 1,
     *      "user_id": "2,4,5",
     *      "is_accept": 0
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Users status is updated successfully"
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
    public function eventUsersStatus(Request $request)
    {
        try{
            $userIDs = explode(',', $request->get('user_id'));
            if($request->get('is_accept') == 1) {
                $updateArray = [
                            'is_cancelled' => 1,
                            'status' => 0
                           ];
            } else {
                $updateArray = [
                            'status' => 1,
                            'is_cancelled' => 0
                           ];
            } 
            foreach ($userIDs as $userID) {
                    EventUser::where('event_id', $request->get('event_id'))
                        ->where('user_id', $userID)
                        ->update($updateArray);   
            }
            return response()->json(['error' => 'false', 'message' => 'Users status is updated successfully']);
        } catch (Exception $ex) {
            return response()->json([
               'error' => 'true',
                'message' => 'Invalid request',
            ]);
        }
    }
}
