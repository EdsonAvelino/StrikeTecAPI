<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events;
use App\EventParticipants;
use App\EventActivities;
use App\EventSessions;

class EventController extends Controller
{
    /**
     * @api {post} /fan/events Create new event
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "multipart/form-data",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} [event_id] Existing Event ID in case of update event
     * @apiParam {String} title Event title
     * @apiParam {int} location_id ID of location
     * @apiParam {String} [description] Description
     * @apiParam {String} start_date Starting date of event, format MM/DD/YYYY 
     * @apiParam {String} start_time Starting time, format HH:II e.g. 15:00
     * @apiParam {String} end_date Ending date of event format MM/DD/YYYY 
     * @apiParam {String} end_time Ending time of event HH:II e.g. 19:00
     * @apiParam {Boolean} [all_day] Event is all day
     * @apiParam {file} [image] Image to be uploaded
     * @apiParamExample {json} Input
     *    {
     *      "title": "EFD fight night",
     *      "location_id": "2",
     *      "description": "",
     *      "start_date": "01/21/2018",
     *      "start_time": "12:00",
     *      "end_date": "01/22/2018",
     *      "end_time": "20:30",
     *      "all_day": "0",
     *      "image": "img.jpeg",
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
     *               "event_id": 1
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
    public function postEvent(Request $request)
    {
        $companyId = \Auth::user()->company_id;
        $imageStoragePath = '/storage/events';
        $validator = \Validator::make($request->all(), ['image' => 'mimes:jpeg,jpg,png']);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors]);
        }

        if ($request->hasFile('image')) {
            $eventInput = $request->file('image');
            $eventImageOrigName = $eventInput->getClientOriginalName();
            $eventImageFileName = pathinfo($eventImageOrigName, PATHINFO_FILENAME);
            $eventImageFileNameExt = pathinfo($eventImageOrigName, PATHINFO_EXTENSION);
            $eventImageName = $eventImageFileName . '-' . time() . '.' . $eventImageFileNameExt;
            $eventInput->move($imageStoragePath, $eventImageName);
            $eventImage = $eventImageName;
        }

        $eventId = Events::create([
            'title' => $request->get('title'),
            'location_id' => (int) $request->get('location_id'),
            'company_id' => $companyId,
            'description' => !empty($request->get('description')) ? $request->get('description') : null,
            'start_date' => date('Y-m-d', strtotime($request->get('start_date'))),
            'start_time' => $request->get('start_time'),
            'end_date' => date('Y-m-d', strtotime($request->get('end_date'))),
            'end_time' => $request->get('end_time'),
            'all_day' => $request->get('all_day'),
            'image' => $eventImage ?? null
        ])->id;

        $data = ['event_id' => $eventId];

        return response()->json(['error' => 'false', 'message' => 'Event has been created successfully', 'data' => $data]);
    }

    /**
     * @api {post} /fan/events/<event_id> Update existing event
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "multipart/form-data",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} [event_id] Existing Event ID in case of update event
     * @apiParam {String} title Event title
     * @apiParam {int} location_id ID of location
     * @apiParam {String} [description] Description
     * @apiParam {String} start_date Starting date of event, format MM/DD/YYYY 
     * @apiParam {String} start_time Starting time, format HH:II e.g. 15:00
     * @apiParam {String} end_date Ending date of event format MM/DD/YYYY 
     * @apiParam {String} end_time Ending time of event HH:II e.g. 19:00
     * @apiParam {Boolean} [all_day] Event is all day
     * @apiParam {file} [image] Image to be uploaded
     * @apiParamExample {json} Input
     *    {
     *      "title": "EFD fight night",
     *      "location_id": "2",
     *      "description": "",
     *      "start_date": "01/21/2018",
     *      "start_time": "12:00",
     *      "end_date": "01/22/2018",
     *      "end_time": "20:30",
     *      "all_day": "0",
     *      "image": "img.jpeg",
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
     *               "event_id": 1
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
    public function postUpdateEvent(Request $request, $eventId)
    {
        $companyId = \Auth::user()->company_id;
        $eventId = (int) $eventId;
        $eventImage = '';
        $imageStoragePath = '/storage/events';
        $validator = \Validator::make($request->all(), ['image' => 'mimes:jpeg,jpg,png']);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors]);
        }

        if ($request->hasFile('image')) {
            $eventInput = $request->file('image');
            $eventImageOrigName = $eventInput->getClientOriginalName();
            $eventImageFileName = pathinfo($eventImageOrigName, PATHINFO_FILENAME);
            $eventImageFileNameExt = pathinfo($eventImageOrigName, PATHINFO_EXTENSION);
            $eventImageName = $eventImageFileName . '-' . time() . '.' . $eventImageFileNameExt;
            $eventInput->move($imageStoragePath, $eventImageName);
            $eventImage = $eventImageName;
        }

        $event = Events::find($eventId);

        if (!$event) {
            return response()->json(['error' => 'true', 'message' => 'Event does not exists']);
        }

        $event->company_id = $companyId;
        $event->title = !empty($request->get('title')) ? $request->get('title') : $event->title;
        $event->location_id = !empty($request->get('location_id')) ? $request->get('location_id') : $event->location_id;
        $event->description = !empty($request->get('description')) ? $request->get('description') : $event->description;
        $event->start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $event->start_date;
        $event->start_time = !empty($request->get('start_time')) ? $request->get('start_time') : $event->start_time;
        $event->end_date = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $event->end_date;
        $event->end_time = !empty($request->get('end_time')) ? $request->get('end_date') : $event->end_date;
        
        $event->all_day = !empty($request->get('all_day')) ? filter_var($request->get('all_day'), FILTER_VALIDATE_BOOLEAN) : $event->all_day;

        if (!empty($eventImage)) {
            $url = env('APP_URL') . $imageStoragePath;
            $pathToFile = str_replace($url, storage_path(), $event->image);
            
            if (file_exists($pathToFile)) {
                unlink($pathToFile); // Delete existing image
            }

            $event->image = $eventImage;
        }

        $event->save();

        return response()->json(['error' => 'false', 'message' => 'Event has been updated successfully', 'data' => ['event_id' => $eventId]]);
    }

    /**
     * @api {get} /fan/activities Get main event activities (Types)
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data list of Event Types 
     * @apiSuccessExample {json} Success
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *          {
     *              "id": 1,
     *              "name": "Speed",
     *              "image_url": "",
     *          },
     *          {
     *              "id": 2,
     *              "name": "Power",
     *              "image_url": "",
     *          },
     *          {
     *              "id": 3,
     *              "name": "Endurance",
     *              "image_url": "",
     *          }
     *      ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function getEventActivityTypes()
    {
        $activityTypes = EventActivityTypes::select('id', 'name', 'description', 'image_url')->get();
        
        return response()->json(['error' => 'false', 'message' => '', 'data' => $activityTypes]);
    }

    /**
     * @api {get} /fan/events Get all my events list
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data All my events list
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "",
     *       "data": [
     *           {
     *                "id": 3,
     *                "company_id": 2,
     *                "location_id": 3,
     *                "title": "UFC FIGHT NIGHT JACARE VS BRUNSON 2",
     *                "description": "Maecenas nulla lacus, pretium pretium nibh quis, g",
     *                "image": null,
     *                "start_date": "2018-01-20",
     *                "start_time": "23:07:00",
     *                "end_date": "2018-01-23",
     *                "end_time": "23:07:00",
     *                "all_day": false,
     *                "status": true,
     *                "company_name": "Monster Energy",
     *                "location_name": "San Francisco ",
     *                "participants_count": 8,
     *                "participants": [
     *                    {
     *                        "id": 10,
     *                        "first_name": "Kim",
     *                        "last_name": "Zion",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 220
     *                    },
     *                    {
     *                        "id": 7,
     *                        "first_name": "Qiang",
     *                        "last_name": "Hu",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 3812
     *                    },
     *                    {
     *                        "id": 31,
     *                        "first_name": "Mia",
     *                        "last_name": "Carleef",
     *                        "photo_url": "",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 8836
     *                    },
     *                    {
     *                        "id": 20,
     *                        "first_name": "Da",
     *                        "last_name": "Mistri",
     *                        "photo_url": null,
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 810
     *                    },
     *                    {
     *                        "id": 25,
     *                        "first_name": "Rack",
     *                        "last_name": "Zukasor",
     *                        "photo_url": null,
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 809
     *                    },
     *                    {
     *                        "id": 14,
     *                        "first_name": "Jack",
     *                        "last_name": "Ma",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 3812
     *                    },
     *                    {
     *                        "id": 19,
     *                        "first_name": "Naiba",
     *                        "last_name": "Puroti",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 220
     *                    },
     *                    {
     *                        "id": 21,
     *                        "first_name": "Jim",
     *                        "last_name": "Kong",
     *                        "photo_url": null,
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 810
     *                    }
     *                ]
     *            },
     *            {
     *                "id": 4,
     *                "company_id": 2,
     *                "location_id": 2,
     *                "title": "UFC FIGHT NIGHT MACHIDA VAN ANDERS",
     *                "description": "Mauris porta tincidunt lectus, sed congue odio lac",
     *                "image": null,
     *                "start_date": "2018-01-19",
     *                "start_time": "23:25:00",
     *                "end_date": "2018-01-24",
     *                "end_time": "23:25:00",
     *                "all_day": false,
     *                "status": true,
     *                "company_name": "Monster Energy",
     *                "location_name": "Manhattan, New York",
     *                "participants_count": 2,
     *                "participants": [
     *                    {
     *                        "id": 7,
     *                        "first_name": "Qiang",
     *                        "last_name": "Hu",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 3812
     *                    },
     *                    {
     *                        "id": 9,
     *                        "first_name": "Jin",
     *                        "last_name": "Xion",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 3812
     *                    }
     *                ]
     *            }
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
    public function getMyEventsList()
    {
        $_eventsList = Events::select(
            '*',
            \DB::raw('company_id as company_name'),
            \DB::raw('location_id as location_name')
        )->withCount('participants')->where('company_id', \Auth::user()->company_id)->get();
        
        $eventsList = [];

        foreach ($_eventsList as $event) {
            $_event = $event->toArray();
            $_event['participants'] = [];
            foreach ($event->participants as $participant) {
                $_event['participants'][] = $participant->user;
            }

            $eventsList[] = $_event;
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $eventsList]);
    }

    /**
     * @api {get} /fan/events/all Get list of all of the events
     * @apiGroup Event
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data All events list
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "",
     *       "data": [
     *           {
     *                "id": 3,
     *                "company_id": 2,
     *                "location_id": 3,
     *                "title": "UFC FIGHT NIGHT JACARE VS BRUNSON 2",
     *                "description": "Maecenas nulla lacus, pretium pretium nibh quis, g",
     *                "image": null,
     *                "start_date": "2018-01-20",
     *                "start_time": "23:07:00",
     *                "end_date": "2018-01-23",
     *                "end_time": "23:07:00",
     *                "all_day": false,
     *                "status": true,
     *                "company_name": "Monster Energy",
     *                "location_name": "San Francisco ",
     *                "participants_count": 8,
     *                "participants": [
     *                    {
     *                        "id": 10,
     *                        "first_name": "Kim",
     *                        "last_name": "Zion",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 220
     *                    },
     *                    {
     *                        "id": 7,
     *                        "first_name": "Qiang",
     *                        "last_name": "Hu",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 3812
     *                    },
     *                    {
     *                        "id": 31,
     *                        "first_name": "Mia",
     *                        "last_name": "Carleef",
     *                        "photo_url": "",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 8836
     *                    },
     *                    {
     *                        "id": 20,
     *                        "first_name": "Da",
     *                        "last_name": "Mistri",
     *                        "photo_url": null,
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 810
     *                    },
     *                    {
     *                        "id": 25,
     *                        "first_name": "Rack",
     *                        "last_name": "Zukasor",
     *                        "photo_url": null,
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 809
     *                    },
     *                    {
     *                        "id": 14,
     *                        "first_name": "Jack",
     *                        "last_name": "Ma",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 3812
     *                    },
     *                    {
     *                        "id": 19,
     *                        "first_name": "Naiba",
     *                        "last_name": "Puroti",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 220
     *                    },
     *                    {
     *                        "id": 21,
     *                        "first_name": "Jim",
     *                        "last_name": "Kong",
     *                        "photo_url": null,
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 810
     *                    }
     *                ]
     *            },
     *            {
     *                "id": 4,
     *                "company_id": 2,
     *                "location_id": 2,
     *                "title": "UFC FIGHT NIGHT MACHIDA VAN ANDERS",
     *                "description": "Mauris porta tincidunt lectus, sed congue odio lac",
     *                "image": null,
     *                "start_date": "2018-01-19",
     *                "start_time": "23:25:00",
     *                "end_date": "2018-01-24",
     *                "end_time": "23:25:00",
     *                "all_day": false,
     *                "status": true,
     *                "company_name": "Monster Energy",
     *                "location_name": "Manhattan, New York",
     *                "participants_count": 2,
     *                "participants": [
     *                    {
     *                        "id": 7,
     *                        "first_name": "Qiang",
     *                        "last_name": "Hu",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 3812
     *                    },
     *                    {
     *                        "id": 9,
     *                        "first_name": "Jin",
     *                        "last_name": "Xion",
     *                        "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                        "gender": "male",
     *                        "user_following": false,
     *                        "user_follower": false,
     *                        "points": 3812
     *                    }
     *                ]
     *            }
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
    public function getAllEventsList()
    {
        $_eventsList = Events::select(
            '*',
            \DB::raw('company_id as company_name'),
            \DB::raw('location_id as location_name')
        )->withCount('participants')->get();
        
        $eventsList = [];

        foreach ($_eventsList as $event) {
            $_event = $event->toArray();
            $_event['participants'] = [];
            foreach ($event->participants as $participant) {
                $_event['participants'][] = $participant->user;
            }

            $eventsList[] = $_event;
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $eventsList]);
    }

    /**
     * @api {get} /fan/user/events Get users with events
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
    public function userEvents()
    {
        $eventStorage = array();
        $eventInfo = array();
        $users = array();
        $companyId = \Auth::user()->company_id;
        $events = Events::select('id')->where('company_id', $companyId)->get()->toArray();
        $eventIds = array_column($events, 'id');

        $eventUsers = EventUser::select('*', \DB::raw('user_id as user_events'))->whereIn('event_id', $eventIds)->groupBy('user_id')->get();

        $count = 0;
        foreach ($eventUsers as $eventUser) {
            $users[$count] = $eventUser->users;
            $users[$count]->events = $eventUser->user_events;
            $count++;
        }

        return response()->json(['error' => 'false', 'message' => 'Users list information', 'data' => $users]);
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
            $userID = \Auth::id();
            $_eventList = Events::select('*', \DB::raw('company_id as company_name'), \DB::raw('location_id as location_name'), \DB::raw('id as count_users_waiting_approval'), \DB::raw('id as is_active'), \DB::raw('id as finalized_at'))
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
     * @api {delete} /fan/events/<event_id> Remove event
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id ID of event which is to delete
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
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
    public function deleteEvent(Request $request, $eventId)
    {
        if (!Events::where('id', $eventId)->exists()) {
            return response()->json(['error' => 'true', 'message' => 'Event does not exists']);
        }

        Events::find($eventId)->delete();
        
        return response()->json([
            'error' => 'false',
            'message' => 'Event has been deleted successfully'
        ]);
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
            $validator = \Validator::make($input, $rules);
            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json(['error' => 'true', 'message' => $errors->first('id')]);
            }
            $event = Events::select('*', \DB::raw('company_id as company_name'), \DB::raw('id as count_users_waiting_approval'), \DB::raw('location_id as location_name'), \DB::raw('id as is_active'), \DB::raw('id as finalized_at'))
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
            $eventDetails = Events::where('user_id', $loggedUserID)
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
        $validator = \Validator::make($request->all(), [
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
            \DB::rollBack();
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
            $_event = Events::select('*', \DB::raw('company_id as company_name'), \DB::raw('location_id as location_name'), \DB::raw('id as count_users_waiting_approval'), \DB::raw('id as is_active'), \DB::raw('id as finalized_at'))
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
