<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events;
use App\EventParticipants;
use App\EventActivities;
use App\EventSessions;

use App\Mail\SendAuthCodeEmail;
use Illuminate\Support\Facades\Mail;

class EventController extends Controller
{
    /**
     * @api {post} /fan/events Create new event
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "multipart/form-data",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} title Event title
     * @apiParam {Number} location_id ID of location
     * @apiParam {String} [description] Description
     * @apiParam {Number} starting_at Starting date of event, format Unix timestamp
     * @apiParam {Number} ending_at Ending date of event format Unix timestamp
     * @apiParam {Boolean} [all_day] Event is all day
     * @apiParam {file} [image] Image to be uploaded
     * @apiParam {Number} activity_type_id Event activity type Id
     * @apiParamExample {json} Input
     *    {
     *      "title": "EFD fight night",
     *      "location_id": "2",
     *      "description": "",
     *      "start_date": 1516201200,
     *      "end_date": 1516044900,
     *      "all_day": false,
     *      "image": "img.jpeg",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Contains created event-id and event-activity-id
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Event has been created",
     *       "data": {
     *               "event_id": 11,
     *               "event_activity_id": 17,
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
        $imageStoragePath = 'storage/events';
        $validator = \Validator::make($request->all(), ['image' => 'mimes:jpeg,jpg,png']);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors->first('image')]);
        }

        if ($request->hasFile('image')) {
            $eventImage = $request->file('image');
            $eventImageOrigName = $eventImage->getClientOriginalName();
            $eventImageFileName = pathinfo($eventImageOrigName, PATHINFO_FILENAME);
            $eventImageFileNameExt = pathinfo($eventImageOrigName, PATHINFO_EXTENSION);

            $eventImageFileName = preg_replace("/[^a-zA-Z]/", "_", $eventImageFileName);

            $eventImageName = 'event_' . md5($eventImageFileName) . '_' . time() . '.' . $eventImageFileNameExt;
            $eventImage->move($imageStoragePath, $eventImageName);
            $eventImage = $eventImageName;
        }
        
        $eventId = Events::create([
            'title' => $request->get('title'),
            'location_id' => (int) $request->get('location_id'),
            'company_id' => $companyId,
            'admin_user_id' => \Auth::id(),
            'description' => !empty($request->get('description')) ? $request->get('description') : null,
            'starting_at' => date('Y-m-d H:i:s', ((int) $request->get('starting_at'))),
            'ending_at' => date('Y-m-d H:i:s', ((int) $request->get('ending_at'))),
            'all_day' => filter_var($request->get('all_day'), FILTER_VALIDATE_BOOLEAN),
            'image' => $eventImage ?? null
        ])->id;

        // Create new activity for this newly created event
        $eventActivity = EventActivities::create([
            'event_id' => $eventId,
            'event_activity_type_id' => $request->get('activity_type_id'),
            'status' => 0
        ]);

        $data = ['event_id' => $eventId, 'event_activity_id' => $eventActivity->id];

        return response()->json(['error' => 'false', 'message' => 'Event has been created', 'data' => $data]);
    }

    /**
     * @api {post} /fan/events/<event_id> Update existing event
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "multipart/form-data",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} [title] Event title
     * @apiParam {int} [location_id] ID of location
     * @apiParam {String} [description] Description
     * @apiParam {Number} [starting_at] Starting date of event. Format Unix timestamp
     * @apiParam {Number} [ending_at] Ending date of event. Format Unix timestamp
     * @apiParam {Boolean} [all_day] Event is all day
     * @apiParam {file} [image] Image to be uploaded
     * @apiParamExample {json} Input
     *    {
     *      "title": "EFD fight night",
     *      "location_id": 3,
     *      "starting_at": 1516043340,
     *      "ending_at": 1516861800,
     *      "all_day": true,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Contains updated event's id
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Event has been updated",
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
        $imageStoragePath = 'storage/events';
        $validator = \Validator::make($request->all(), ['image' => 'mimes:jpeg,jpg,png']);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors]);
        }

        $event = Events::find($eventId);

        if (!$event) {
            return response()->json(['error' => 'true', 'message' => 'Event does not exists']);
        }

        if ($request->hasFile('image')) {
            $eventImage = $request->file('image');
            $eventImageOrigName = $eventImage->getClientOriginalName();
            $eventImageFileName = pathinfo($eventImageOrigName, PATHINFO_FILENAME);
            $eventImageFileNameExt = pathinfo($eventImageOrigName, PATHINFO_EXTENSION);

            $eventImageFileName = preg_replace("/[^a-zA-Z]/", "_", $eventImageFileName);

            $eventImageName = 'event_' . md5($eventImageFileName) . '_' . time() . '.' . $eventImageFileNameExt;
            $eventImage->move($imageStoragePath, $eventImageName);
            $eventImage = $eventImageName;
        }

        $event->title = !empty($request->get('title')) ? $request->get('title') : $event->title;
        $event->location_id = !empty($request->get('location_id')) ? $request->get('location_id') : $event->location_id;
        $event->description = !empty($request->get('description')) ? $request->get('description') : $event->description;
        $event->starting_at = !empty($request->get('starting_at')) ? date('Y-m-d H:i:s', $request->get('starting_at')) : $event->starting_at;
        $event->ending_at = !empty($request->get('ending_at')) ? date('Y-m-d H:i:s', $request->get('ending_at')) : $event->ending_at;
        
        $event->all_day = !empty($request->get('all_day')) ? filter_var($request->get('all_day'), FILTER_VALIDATE_BOOLEAN) : $event->all_day;

        if (isset($eventImage) && !empty($eventImage)) {
            $pathToFile = storage_path('events/'.basename($event->image));

            if (file_exists($pathToFile)) {
                unlink($pathToFile); // Delete existing image
            }

            $event->image = $eventImage;
        }

        $event->save();

        return response()->json(['error' => 'false', 'message' => 'Event has been updated', 'data' => ['event_id' => $eventId]]);
    }

    /**
     * @api {get} /fan/activities Get main event activities (Types)
     * @apiGroup Events
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
        $activityTypes = \App\EventActivityTypes::select('id', 'name', 'description', 'image_url')->get();
        
        return response()->json(['error' => 'false', 'message' => '', 'data' => $activityTypes]);
    }

    /**
     * @api {get} /fan/events Get all my events list
     * @apiGroup Events
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
     *                "starting_at": 1518577200,
     *                "ending_at": 1518674400,
     *                "all_day": false,
     *                "status": ture,
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
     *                "starting_at": 1517650200,
     *                "ending_at": 1517650200,
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
        )->withCount('participants')
        ->where('company_id', \Auth::user()->company_id)->where('admin_user_id', \Auth::id())->get();
        
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
     * @apiGroup Events
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
     *                "starting_at": 1516126440,
     *                "ending_at": 1516469820,
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
     *                "starting_at": 1516043580,
     *                "end_date": 1516095780,
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
        $_eventsListQuery = Events::select(
            '*',
            \DB::raw('company_id as company_name'),
            \DB::raw('location_id as location_name')
        )->withCount('participants')->where('admin_user_id', \Auth::id());

        if (\Auth::user()->is_fan_app_admin) {
            $_eventsListQuery->where('company_id', \Auth::user()->company_id);
        }
        
        $_eventsList = $_eventsListQuery->get();
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
     * @api {delete} /fan/events/<event_id> Remove event
     * @apiGroup Events
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
     *       "message": "Event has been deleted",
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
            'message' => 'Event has been deleted'
        ]);
    }

    /**
     * @api {get} /fan/events/<event_id>/activities Get list of event activities with users
     * @apiGroup Events
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event activites list
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   "error": "false",
     *   "message": "",
     *   "data": {
     *         "id": 7,
     *         "company_id": 4,
     *         "location_id": 2,
     *         "title": "UFC FIGHT NIGHT TBA VS TBD",
     *         "description": "Sapien ultrices, quis convallis tortor varius vest",
     *         "image": null,
     *         "starting_at": 1516043580,
     *         "ending_at": 1516063380,
     *         "all_day": false,
     *         "status": true,
     *         "company_name": "Bellator MMA",
     *         "location_name": "Manhattan, New York",
     *         "participants_count": 10,
     *         "activities": [
     *             {
     *                 "id": 12,
     *                 "event_id": 7,
     *                 "event_activity_type_id": 2,
     *                 "status": 2,
     *                 "concluded_at": 1513954966,
     *                 "created_at": 1513954966,
     *                 "updated_at": 1513962824,
     *                 "type_name": "Power",
     *                 "participants": [
     *                     {
     *                          "id": 1,
     *                          "first_name": "Xion",
     *                          "last_name": "King",
     *                          "photo_url": "https://graph.facebook.com/1234567890/picture?type=large",
     *                          "is_finished": false
     *                     },
     *                     {
     *                          "id": 1,
     *                          "first_name": "Jack",
     *                          "last_name": "Ma",
     *                          "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                          "is_finished": true
     *                     },
     *                     {
     *                          "id": 1,
     *                          "first_name": "Kely",
     *                          "last_name": "Flynn",
     *                          "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                          "is_finished": false
     *                     }
     *                 ]
     *             },
     *             {
     *                 "id": 14,
     *                 "event_id": 7,
     *                 "event_activity_type_id": 2,
     *                 "status": 2,
     *                 "concluded_at": 1514182122,
     *                 "created_at": 1514182122,
     *                 "updated_at": 1514182164,
     *                 "type_name": "Power",
     *                 "participants": [
     *                     {
     *                          "id": 1,
     *                          "first_name": "Sarah",
     *                          "last_name": "Milong",
     *                          "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                          "is_finished": true
     *                     },
     *                     {
     *                          "id": 2,
     *                          "first_name": "Zuck",
     *                          "last_name": "Jack",
     *                          "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                          "is_finished": false
     *                     },
     *                     {
     *                          "id": 1,
     *                          "first_name": "Karl",
     *                          "last_name": "Lobster",
     *                          "photo_url": "https://graph.facebook.com/123456789/picture?type=large",
     *                          "is_finished": false
     *                     }
     *                  ]
     *             }
     * }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getEventActivities(Request $request, $eventId)
    {
        $eventId = (int) $eventId;
        
        if (!$eventId || !($event = Events::where('id', $eventId)->exists())) {
            return response()->json(['error' => 'true', 'message' => 'Event does not exists']);
        }

        $event = Events::select(
            '*',
            \DB::raw('company_id as company_name'),
            \DB::raw('location_id as location_name')
        )->where('id', $eventId)->withCount('participants')->first();

        $eventWithActivities = $event->toArray();
        $eventActivities = [];

        foreach ($event->activities as $eventActivity) {
            $_eventActivity = $eventActivity->toArray();
            $_eventActivity['type_name'] = $eventActivity->type->name;

            $participants = [];

            foreach ($eventActivity->participants->take(5) as $participant) {
                $_participant['id'] = $participant->user->id;
                $_participant['first_name'] = $participant->user->first_name;
                $_participant['last_name'] = $participant->user->last_name;
                $_participant['photo_url'] = $participant->user->photo_url;
                $_participant['is_finished'] = (bool) $participant->user->is_finished;

                $participants[] = $_participant;
            }

            $_eventActivity['participants'] = $participants;
            $eventActivities[] = $_eventActivity;
        }

        $eventWithActivities['activities'] = $eventActivities;

        return response()->json(['error' => 'false', 'message' => '', 'data' => $eventWithActivities]);
    }

    /**
     * @api {post} /fan/events/{eventId}/activities Add activity to event
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} activity_type_id Type id of activity e.g. 1 = Speed, 2 = Power & 3 = Endurance
     * @apiParamExample {json} Input
     *    {
     *      "activity_type_id": "1",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *       "error": "false",
     *       "message": "Activity has been added",
     *       "data": {
     *           "id": 50,
     *           "event_id": 2,
     *           "event_activity_type_id": 2,
     *           "status": false,
     *           "created_at": 1517915117,
     *           "updated_at": 1517915117,
     *           "type_name": "Power",
     *           "participants": []
     *      }
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function postAddEventActivity(Request $request, $eventId)
    {
        $eventActivityId = EventActivities::create([
            'event_id' => $eventId,
            'event_activity_type_id' => $request->get('activity_type_id')
        ])->id;
        
        $_eventActivity = EventActivities::where('id', $eventActivityId)->first();

        $eventActivity = $_eventActivity->toArray();
        $eventActivity['type_name'] = $_eventActivity->type->name;
        $eventActivity['participants'] = $_eventActivity->participants;
        
        return response()->json([ 'error' => 'false', 'message' => 'Activity has been added', 'data' => $eventActivity]);
    } 
    
    /**
     * @api {delete} /fan/events/{eventId}/activities Remove activity from event
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} event_activity_id Id of Event Activity which is to be remove
     * @apiParamExample {json} Input
     *    {
     *      "event_activity_id" : 2
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Activity has been removed",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function deleteEventActivity(Request $request, $eventId)
    {   
        $validator = \Validator::make($request->all(), [
            'event_activity_id' => 'required|exists:event_activities,id',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('event_activity_id')]);
        }

        EventActivities::where('id', $request->get('event_activity_id'))->delete();

        return response()->json([
            'error' => 'false',
            'message' => 'Activity has been removed'
        ]);
    }

    /**
     * @api {post} /fan/events/activities/users Register participants to event activities
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} user_id list of user Ids comma seperated e.g. 1,2,3...n
     * @apiParam {int} event_activity_id Id of event activity
     * @apiParamExample {json} Input
     *    {
     *      "event_activity_id": "2",
     *      "user_id": "1,2,3",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *       "error": "false",
     *       "message": "Users have been added to event activity",
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function postUsersToEventActivity(Request $request)
    {
        $eventActivityId = $request->get('event_activity_id');
        $userIds = explode(',', $request->get('user_id'));

        foreach ($userIds as $userId) {
            $exists = EventParticipants::where('event_activity_id', $eventActivityId)->where('user_id', $userId)->exists();
        
            if (!$exists) {
                EventParticipants::create([
                    'event_activity_id' => $eventActivityId,
                    'user_id' => $userId,
                    'is_finished' => null,
                    'joined_via' => 'F'
                ]);
            }
        }

        return response()->json(['error' => 'false', 'message' => 'Users have been added to event activity']);
    }

    /**
     * @api {delete} /fan/events/activities/users Remove participants from event activities
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_activity_id Id of event activity
     * @apiParam {int} user_id list of user Ids comma seperated e.g. 1,2,3...n
     * @apiParamExample {json} Input
     *    {
     *      "event_activity_id": "2",
     *      "user_id": "1,2,3",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Users haave been removed",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function deleteUsersFromEventActivity(Request $request)
    {
        $eventActivityId = $request->get('event_activity_id');
        $userIds = explode(',', $request->get('user_id'));

        foreach ($userIds as $userId) {
            if ($userId) {
                EventParticipants::where('event_activity_id', $eventActivityId)->where('user_id', $userId)->delete();
            }
        }

        return response()->json([
            'error' => 'false',
            'message' => 'Users have been removed'
        ]);
    }

    /**
     * @api {post} /fan/events/activities/users/authorize Authorize participant for event activities
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_activity_id Id of event activity
     * @apiParam {int} user_id ID of user who needs to authorize
     * @apiParamExample {json} Input
     *    {
     *      "event_activity_id": "2",
     *      "user_id": "7",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Contains generated authorization code for user
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Processed",
     *       "data": {
     *          "user_id": 7,
     *          "event_activity_id": 2,
     *          "auth_code": "d4735e3a2"
     *        },
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function authorizeUserForEventActivity(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'event_activity_id' => 'required|exists:event_activities,id',
            'user_id' => 'required|exists:event_participants,user_id',
        ]);

        if ($validator->fails()) { 
            $errors = $validator->errors();

            if ($errors->first('event_activity_id'))
                return response()->json(['error' => 'true', 'message' =>  $errors->first('event_activity_id')]);
            elseif ($errors->first('user_id'))
                return response()->json(['error' => 'true', 'message' =>  $errors->first('user_id')]);
        }

        $eventActivityId = $request->get('event_activity_id');
        $userId = $request->get('user_id');

        $user = \App\User::find($userId);

        $authCode = substr(hash('sha256', $eventActivityId + $userId), 0, 9);
        EventParticipants::where('event_activity_id', $eventActivityId)->where('user_id', $userId)
            ->update(['auth_code' => $authCode]);

        // Mail to user their auth
        Mail::to($user->email)->send(new SendAuthCodeEmail($user, $authCode));

        return response()->json(['error' => 'false', 'message' => 'Authorization requested for user', 'data' => ['user_id' => $user->id, 'event_activity_id' => (int) $eventActivityId, 'auth_code' => $authCode]]);
    }

    /**
     * @api {post} /fan/events/activities/status Update status of event-activity
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_activity_id Id of event-activity
     * @apiParam {int=1,2,3} status Status of event-activity. status = 1 > activated / running now, status = 2 > paused, status = 3 > finished
     * @apiParamExample {json} Input
     *    {
     *      "event_activity_id": 1,
     *      "status": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Activity status updated",
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
    function postStatusUpdateEventActivity(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'event_activity_id' => 'required|exists:event_activities,id',
            'status' => 'required|in:1,2,3',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->first('event_activity_id'))
                return response()->json(['error' => 'true', 'message' => $errors->first('event_activity_id')]);
            else
                return response()->json(['error' => 'true', 'message' => $errors->first('status')]);
        }

        $eventActivityId = $request->get('event_activity_id');

        EventActivities::where('id', $eventActivityId)
            ->update(['status' => $request->get('status'), 'concluded_at' => date('Y-m-d H:i:s')]);

        return response()->json([
            'error' => 'false',
            'message' => 'Activity status updated',
        ]);
    }

    /**
     * @api {post} /fan/events/activities/sessions Upload event-activity's punches
     * @apiGroup Events
     * @apiHeader {String} authorization Authorization value
     * @apiHeader {String} content-type Content-Type set to "application/json"
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *       "Content-Type": "application/json"
     *     }
     * @apiParam {json} data Json formatted sessions data
     * @apiParamExample {json} Input
     * {
     * "data": [
     *      {
     *    "participant_data": {
     *      "activity_id": 2,
     *      "activity_time": 0,
     *      "end_time": 0,
     *      "event_id": 68,
     *      "gloves_weight": 0,
     *      "participant_id": 109,
     *      "prepare_time": "30",
     *      "start_time": 1513955976946,
     *      "sync": 0,
     *      "warning_time": "30",
     *      "weight": 200,
     *      "rowId": 3
     *    },
     *    "participant_stats_data": {
     *      "avg_force": 404.4237288135593,
     *      "avg_speed": 21.305084745762713,
     *      "finished": 0.0,
     *      "best_time": 0.0,
     *      "max_force": 593.0,
     *      "max_speed": 34.0,
     *      "participant_fk": 0.0,
     *      "punches_count": 59,
     *      "sync": 0.0
     *    },
     *    "participant_punch_data": [
     *      {
     *        "force": 306,
     *        "hand": "R",
     *        "punch_duration": 0.5,
     *        "punch_time": "1513955976999",
     *        "punch_type": "U",
     *        "speed": 6,
     *        "sync": 0
     *      },
     *      {
     *        "force": 356,
     *        "hand": "L",
     *        "punch_duration": 0.5,
     *        "punch_time": "1513955977984",
     *        "punch_type": "H",
     *        "speed": 6,
     *        "sync": 0
     *      },
     *    ]
     *  }
     *  ]
     * }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     * HTTP/1.1 200 OK
     * {
     *   {
     *       "error": "false",
     *       "message": "Data stored successfully",
     *   }
     * }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function storeEventSessions(Request $request)
    {  
        $participantData = $request->get('participant_data');
        $paricipantSessionData = $request->get('participant_stats_data');
        $paricipantPunchData = $request->get('participant_punch_data');

        // Creates session
        $_session = EventSession::create([
            'participant_id' => $participantData['participant_id'],
            'event_id' => $participantData['event_id'],
            'activity_id' => $participantData['activity_id'],
            'start_time' => $participantData['start_time'],
            'end_time' => ($participantData['end_time']) ? $participantData['end_time'] : '',
            'plan_id' => !empty($participantData['plan_id']) ? $participantData['plan_id'] : '',
            'avg_speed' => $paricipantSessionData['avg_speed'],
            'avg_force' => $paricipantSessionData['avg_force'],
            'punches_count' => $paricipantSessionData['punches_count'],
            'max_force' => $paricipantSessionData['max_force'],
            'max_speed' => $paricipantSessionData['max_speed'],
            'best_time' => $paricipantSessionData['best_time']
        ]);
        
        // Store Punchases
        foreach ($paricipantPunchData as $val) {
            $_punch = EventSessionPunches::create([
                'event_session_id' =>  $_session->id,
                'punch_time' => $val['punch_time'],
                'punch_duration' => $val['punch_duration'],
                'force' => $val['force'],
                'speed' => $val['speed'],
                'punch_type' => strtoupper($val['punch_type']),
                'hand' => strtoupper($val['hand']),
            ]);
        }

        return response()->json([
            'error' => 'false',
            'message' => 'Data stored successfully',
        ]);
    }
    
    /**
     * @api {get} /fan/events/activities/<event_activity_id>/leaderboard Get leaderboard of event activity
     * @apiGroup Events
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} session Sessions information
     * @apiSuccessExample {json} Success 
     * {
     * "error": "false",
     * "message": "",
     * "data": [
     *   {
     *       "event_id": 68,
     *       "activity_id": 3,
     *       "status": true,
     *        "event_sessions": [
     *       {
     *           "id": 3,
     *           "participant_id": 12,
     *           "event_id": 2,
     *           "activity_id": 3,
     *           "start_time": 1513955976946,
     *           "end_time": 0,
     *           "plan_id": 0,
     *           "avg_speed": 21.305084745763,
     *           "avg_force": 404.42372881356,
     *           "punches_count": 59,
     *           "max_speed": 137,
     *           "max_force": 593,
     *           "best_time": "0",
     *           "created_at": "2017-12-22 15:19:36",
     *           "updated_at": "2017-12-26 20:11:01",
     *           "user": {
     *               "id": 12,
     *               "first_name": "Anchal",
     *               "last_name": "Gupta",
     *               "name": "Anchal Gupta",
     *               "photo_url": null
     *           }
     *       },
     *       {
     *           "id": 2,
     *           "participant_id": 7,
     *           "event_id": 2,
     *           "activity_id": 3,
     *           "start_time": 1513955976946,
     *           "end_time": 0,
     *           "plan_id": 0,
     *           "avg_speed": 21.305084745763,
     *           "avg_force": 404.42372881356,
     *           "punches_count": 59,
     *           "max_speed": 38,
     *           "max_force": 593,
     *           "best_time": "0",
     *           "created_at": "2017-12-22 15:19:36",
     *           "updated_at": "2017-12-26 20:10:59",
     *           "user": {
     *               "id": 7,
     *               "first_name": "Qiang",
     *               "last_name": "Hu",
     *               "name": "Qiang Hu",
     *               "photo_url": "http://172.16.11.45/storage/profileImages/sub-1509460359.png"
     *           }
     *       }
     *   ]
     *     }
     *   ]
     *  }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getLeaderboardByEventActivity(Request $request)
    {   
        $eventID = $request->get('event_id');
        $activityID = $request->get('activity_id');

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
        }])->where('event_id', $eventID)->where('activity_id', $activityID)->first();
        
        if (!empty($leaderBoardDetails)) {
            return response()->json([
                'error' => 'false',
                'message' => '',
                'data' => $leaderBoardDetails,
            ]);
        }
    }
}
