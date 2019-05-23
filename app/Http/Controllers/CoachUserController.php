<?php

namespace App\Http\Controllers;

use App\Client;
use App\ClientSessions;
use App\Leaderboard;
use App\Battles;
use App\SessionRounds;
use App\SessionRoundPunches;
use App\GameLeaderboard;
use App\ComboTags;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class CoachUserController extends Controller
{
    /**
     * @var \Tymon\JWTAuth\JWTAuth
    */
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
       
    }

    /**
     * @api {post} /coach/clients Add new client
     * @apiGroup Coach
     * @apiHeader {String} Content-Type application/form-data
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/form-data"
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} [first_name] First Name
     * @apiParam {String} [last_name] Last Name
     * @apiParam {string} [profile_image] Image of client profile 
     * @apiParam {String="male","female"} [gender] Gender
     * @apiParam {Date} [birthday] Birthday in MM-DD-YYYY e.g. 09/11/1987
     * @apiParam {Number} [weight] Weight
     * @apiParam {Number} [height_feet] Height (Feet Value)
     * @apiParam {Number} [height_inches] Height (Inches Value)
     * @apiParam {Boolean} [is_spectator] Spectator true / false
     * @apiParam {String} [stance] Stance
     * @apiParam {Boolean} [show_tip] Show tips true / false
     * @apiParam {Boolean} [is_coach] Coach/Boxer (Coach: true, Boxer: false)
     * @apiParam {String} [skill_level] Skill level of client
     * @apiParam {String} [photo_url] Client profile photo-url
     * @apiParam {Number} [city_id] City ID
     * @apiParam {Number} [state_id] State ID
     * @apiParam {Number} [country_id] Country ID
     * @apiParamExample {json} Input
     *    {
     *      "first_name": "John",
     *      "last_name": "Smith",
     *      "gender": "male",
     *      "birthday": "09/11/1987",
     *      "weight": 25,
     *      "height_feet": 5,
     *      "height_inches": 11,
     *      "is_spectator": true,
     *      "stance": "traditional",
     *    }
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Client has been added successfully"
     *          "data": {
     *              "client_id": 54
     *          }
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function addClient(Request $request)
    {         
        $validator = \Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'nullable|in:male,female',
            'birthday' => 'nullable|date',
            // 'profile_image' => 'nullable|mimes:jpeg,jpg,png'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors]);
        }

        $imageStoragePath = config('striketec.storage.users');

        if (\Auth::user()->is_coach == false) {
            return response()->json(['error' => 'true', 'message' => 'It must be a coach user']);
        }

        \Log::info(\Auth::user());
        \Log::info(\Auth::id());

        if ($request->hasFile('profile_image')) {
            $clientProfileImage = $request->file('profile_image');
            $clientProfileImageOrigName = $clientProfileImage->getClientOriginalName();
            
            $profilePicName = pathinfo($clientProfileImageOrigName, PATHINFO_FILENAME);
            $profilePicExt = pathinfo($clientProfileImageOrigName, PATHINFO_EXTENSION);
            
            $profilePicName = preg_replace("/[^a-zA-Z]/", "_", $profilePicName);

            $clientProfileImageName = 'u_'. md5($profilePicName) . '_' . time() . '.' . $profilePicExt;
            $clientProfileImage->move($imageStoragePath, $clientProfileImageName);
            
            $clientProfileImage = $clientProfileImageName;
        }

        // Creates a new client
        $newClient = [
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'coach_user' => \Auth::id(),
            'show_tip' => 1,
            'is_spectator' => 1,
            'is_coach' => 0,
            'login_count' => 0,
            'has_sensors' => 0
        ];

        $newClient["gender"] = ($request->get('gender')) ?? NULL;

        $birthday = $request->get('birthday') ?
            date('Y-m-d', strtotime($request->get('birthday'))) : NULL;
        $newClient["birthday"] = $birthday;

        $newClient["weight"] = $request->get('weight') ?? NULL;
        $newClient["height_feet"] = $request->get('height_feet') ?? NULL;
        $newClient["height_inches"] = $request->get('height_inches') ?? NULL;

        $isSpectator = filter_var($request->get('is_spectator'), FILTER_VALIDATE_BOOLEAN);
        $newClient["is_spectator"] = $request->get('is_spectator') ? $isSpectator : NULL;

        $showTip = filter_var($request->get('show_tip'), FILTER_VALIDATE_BOOLEAN);
        $newClient["show_tip"] = $request->get('show_tip') ? $showTip : NULL;

        $newClient["skill_level"] = $request->get('skill_level') ?? NULL;
        $newClient["stance"] = $request->get('stance') ?? null;
        // $newClient["photo_url"] = (isset($clientProfileImage) && !empty($clientProfileImage)) ? $clientProfileImage : NULL;

        $newClient["city_id"] = $request->get('city_id') ?? NULL;
        $newClient["state_id"] = $request->get('state_id') ?? NULL;
        $newClient["country_id"] = $request->get('country_id') ?? NULL;

        try {
            $client = Client::create($newClient);

            return response()->json([
                'error' => 'false',
                'message' => 'Client has been added successfully',
                'data' => ['client_id' => $client->id]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @api {post} /coach/client/sensors Update client's sensor
     * @apiGroup Coach
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} [left_hand_sensor] Left hand sensor
     * @apiParam {String} [right_hand_sensor] Right hand sensor
     * @apiParam {String} [left_kick_sensor] Left kick sensor
     * @apiParam {String} [right_kick_sensor] Right kick sensor

     * @apiParamExample {json} Input
     *    {
     *      "client_id": 54,
     *      "left_hand_sensor": 54:6C:0E:15:17:C5,
     *      "right_hand_sensor": 54:6C:0E:03:F3:ED,
     *    }
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Updated successfully"
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function updateSensors(Request $request)
    {
        // Find client who have shared his sensors
        $leftHandSensor = $request->get('left_hand_sensor');
        $rightHandSensor = $request->get('right_hand_sensor');

        $_client = Client::select('id', 'is_sharing_sensors')
            ->where('coach_user', \Auth::id())
            ->where(function ($query) use ($leftHandSensor) {
                $query->where('left_hand_sensor', $leftHandSensor)->orWhere('right_hand_sensor', $leftHandSensor);
            })->where(function ($query) use ($rightHandSensor) {
                $query->where('left_hand_sensor', $rightHandSensor)->orWhere('right_hand_sensor', $rightHandSensor);
            })->where('is_sharing_sensors', '1');

        // In case, client exists with requested mac address of sensors and sharing sensors
        // then no need to store into db, just success response
        if ($_client->exists() && (($_client = $_client->first())->is_sharing_sensors)) {
            try {
                $client = Client::find($request->get('client_id'));
                $client->left_hand_sensor = ($request->get('left_hand_sensor')) ?? $client->left_hand_sensor;
                $client->right_hand_sensor = ($request->get('right_hand_sensor')) ?? $client->right_hand_sensor;
                $client->left_kick_sensor = ($request->get('left_kick_sensor')) ?? $client->left_kick_sensor;
                $usclienter->right_kick_sensor = ($request->get('right_kick_sensor')) ?? $client->right_kick_sensor;

                $client->is_spectator = 0;
                $client->has_sensors = 1;

                $client->save();
                return response()->json([
                    'error' => 'false',
                    'message' => 'Updated successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'true',
                    'message' => $e->getMessage()
                ]);
            }
        }

        $validator = Validator::make($request->all(), [
            'left_hand_sensor' => 'nullable|unique:users,left_hand_sensor,' . \Auth::id() . '|unique:users,right_hand_sensor,' . \Auth::id(),
            'right_hand_sensor' => 'nullable|unique:users,right_hand_sensor,' . \Auth::id() . '|unique:users,left_hand_sensor,' . \Auth::id(),
            'left_kick_sensor' => 'nullable|unique:users,left_kick_sensor,' . \Auth::id() . '|unique:users,right_kick_sensor,' . \Auth::id(),
            'right_kick_sensor' => 'nullable|unique:users,right_kick_sensor,' . \Auth::id() . '|unique:users,left_kick_sensor,' . \Auth::id(),
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->get('left_hand_sensor'))
                return response()->json(['error' => 'true', 'message' => 'Invalid MAC address for LHS']);
            elseif ($errors->get('right_hand_sensor'))
                return response()->json(['error' => 'true', 'message' => 'Invalid MAC address for RHS']);
            elseif ($errors->get('left_kick_sensor'))
                return response()->json(['error' => 'true', 'message' => 'Invalid MAC address for LKS']);
            elseif ($errors->get('right_kick_sensor'))
                return response()->json(['error' => 'true', 'message' => 'Invalid MAC address for RKS']);
        }

        try {
            $client = Client::find($request->get('client_id'));

            $client->left_hand_sensor = ($request->get('left_hand_sensor')) ?? $client->left_hand_sensor;
            $client->right_hand_sensor = ($request->get('right_hand_sensor')) ?? $client->right_hand_sensor;
            $client->left_kick_sensor = ($request->get('left_kick_sensor')) ?? $client->left_kick_sensor;
            $client->right_kick_sensor = ($request->get('right_kick_sensor')) ?? $client->right_kick_sensor;

            $client->is_spectator = 0;
            $client->has_sensors = 1;

            $client->save();

            return response()->json([
                'error' => 'false',
                'message' => 'Updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @api {get} /coach/client/score Get client's score
     * @apiGroup Game
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Integer} game_id ID of game of which score you want
     * @apiParamExample {json} Input
     *    {
     *      "game_id": 1,
     *      "client_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} clients List of clients followed by search term
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": {
     *              "score": 3,
     *              "distance": 24
     *          }
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invaild request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getClientGameScores(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'game_id'    => 'required|exists:games,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('game_id')]);
        }

        $gameId = (int)$request->get('game_id');

        $leaderboardData = GameLeaderboard::select('game_id', 'score', 'distance')->where('client_id', $request->get('client_id'))->where('game_id', $gameId)->first();

        $data = new \stdClass;

        if ($leaderboardData) {
            $score = $leaderboardData->score;

            switch ($leaderboardData->game_id) {
                case 1:
                    $score = (float)number_format($score, 3);
                    break; // Reaction time
                case 2:
                    $score = (int)$score;
                    break;
                case 3:
                    $score = (int)$score;
                    break;
                case 4:
                    $score = (int)$score;
                    break;
            }

            $data->score = $score;
            $data->distance = (float)number_format($leaderboardData->distance, 1);
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $data
        ]);
    }

    /**
     * @api {get} /coach/client/progress Get client's training progress
     * @apiGroup Coach
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParamExample {json} Input
     *    {
     *      "client_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Summary of total trained grouping by skill-level
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": {
     *              "Beginner": {
     *                  "trained": 3,
     *                  "total": 9
     *              },
     *              "Intermediate": {
     *                  "trained": 0,
     *                  "total": 2
     *              },
     *              "Advanced": {
     *                  "trained": 4,
     *                  "total": 10
     *              }
     *          }
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invaild request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getClientProgress(Request $request)
    {
        $totalCombos = ComboTags::select('filter_id', \DB::raw('COUNT(combo_id) as combos_count'))->groupBy('filter_id')->get();

        $result = [];

        foreach ($totalCombos as $row) {
            $combos = ComboTags::select('combo_id')->where('filter_id', $row->filter_id)->get()->pluck('combo_id')->toArray();

            $clientTrained = ClientSessions::select('plan_id', \DB::raw('COUNT(id) as total'))
                ->where('client_id', $request->get('client_id'))
                ->where('type_id', \App\Types::COMBO)
                ->whereIn('plan_id', $combos)
                // ->whereRaw('YEARWEEK(FROM_UNIXTIME(start_time / 1000), 1) = YEARWEEK(CURDATE(), 1)')
                ->groupBy('plan_id')->get()->count();

            $result[$row->filter->filter_name] = ['trained' => $clientTrained, 'total' => $row->combos_count];
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $result
        ]);
    }

    /**
     * @api {get} /coach/clients Get list of clients (Client Database)
     * @apiGroup Coach
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParam {String} [query] Search clients by name or email
     * @apiParamExample {json} Input
     *    {
     *      "start": 0,
     *      "limit": 30,
     *      "query": "jack"
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event list information
     * @apiSuccessExample {json} Success
     * {
     *       "error": "false",
     *       "message": "",
     *       "data": [
     *           {
     *               "id": 1,
     *               "first_name": "Jack",
     *               "last_name": "Xeing",
     *               "photo_url": "http://example.com/users/user_pic-1513164799.jpg",
     *           },
     *           {
     *               "id": 2,
     *               "first_name": "Mel",
     *               "last_name": "Sultana",
     *               "photo_url": "http://example.com/users/user_pic-1513164799.jpg"
     *           },
     *           {
     *               "id": 3,
     *               "first_name": "Karl",
     *               "last_name": "Lobster",
     *               "photo_url": "http://example.com/users/user_pic-1513164799.jpg"
     *           }
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
    public function getClientsList(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        
        $query = trim($request->get('query') ?? NULL);

        $_clients = Client::select('id', 'first_name', 'last_name', 'photo_url', 'skill_level', 'weight')
                                ->where('coach_user', \Auth::id());

        if ($query) {
            $_clients->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%$query%")
                    ->orWhere('last_name', 'LIKE', "%$query%");
            });
        }
        
        $clients = $_clients->offset($offset)->limit($limit)->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $clients]);
    }

    /**
     * @api {get} /coach/client/<client_id> Get client information
     * @apiGroup Coach
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {number} [client_id] Client's ID, if not given it will give current logged in client's info

     * @apiParamExample {json} Input
     *    {
     *      "client_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} client Client's information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "client": {
     *              "id": 1,
     *              "first_name": "John",
     *              "last_name": "Smith",
     *              "gender": null,
     *              "birthday": "1975-05-09",
     *              "weight": null,
     *              "height_feet": 5,
     *              "height_inches": 11,
     *              "left_hand_sensor": null,
     *              "right_hand_sensor": null,
     *              "left_kick_sensor": null,
     *              "right_kick_sensor": null,
     *              "is_spectator": 0,
     *              "stance": null,
     *              "show_tip": 1,
     *              "is_coach": 0,
     *              "skill_level": null,
     *              "photo_url": "http://image.example.com/profile/pic.jpg",
     *              "updated_at": "2016-02-10 15:46:51",
     *              "created_at": "2016-02-10 15:46:51",
     *              "preferences": {
     *                  "public_profile": 0,
     *                  "show_achivements": 1,
     *                  "show_training_stats": 1,
     *                  "show_challenges_history": 1
     *              },
     *              "country": {
     *                  "id": 14,
     *                  "name": "Austria"
     *              },
     *              "state": {
     *                  "id": 286,
     *                  "country_id": 14,
     *                  "name": "Oberosterreich"
     *              },
     *              "city": {
     *                  "id": 6997,
     *                  "state_id": 286,
     *                  "name": "Pettenbach"
     *              },
     *             "points": 2999,
     *             "total_time_trained": 5235,
     *             "total_time_trained": 15090,
     *             "total_day_trained": 32,
     *             "avg_speed": 438,
     *             "avg_force": 7992,
     *             "punches_count": 5854,
     *             "avg_count": 6,
     *             "lose_counts": 1,
     *             "win_counts": 2,
     *             "finished_battles": [
     *                 {
     *                     "battle_id": 119,
     *                     "shared": false,
     *                     "winner": {
     *                         "id": 20,
     *                         "first_name": "da",
     *                         "last_name": "cheng",
     *                         "photo_url": null,
     *                         "points": 323
     *                     },
     *                     "loser": {
     *                         "id": 7,
     *                         "first_name": "Qiang",
     *                         "last_name": "Hu",
     *                         "photo_url": "http://image.example.com/profileImages/sub-1509460359.png",
     *                         "points": 5854
     *                     }
     *                 },
     *                 {
     *                     "battle_id": 120,
     *                     "shared": false,
     *                     "winner": {
     *                         "id": 7,
     *                         "first_name": "Qiang",
     *                         "last_name": "Hu",
     *                         "photo_url": "http://image.example.com/profileImages/sub-1509460359.png",
     *                         "points": 5854
     *                     },
     *                     "loser": null
     *                 },
     *                 {
     *                     "battle_id": 32,
     *                     "shared": false,
     *                     "winner": {
     *                         "id": 7,
     *                         "first_name": "Qiang",
     *                         "last_name": "Hu",
     *                         "photo_url": "http://image.example.com/profile/sub-1509460359.png",
     *                         "points": 5854
     *                     },
     *                     "loser": {
     *                         "id": 1,
     *                         "first_name": "Nawaz",
     *                         "last_name": "Me",
     *                         "photo_url": null,
     *                         "points": 2768
     *                     }
     *                 }
     *             ],
     *           "achievements": [
     *           {  
     *              "achievement_id": 1,
     *              "achievement_name": "belt",
     *              "badge_name": "belt",
     *              "description": "belt",
     *              "image": "http://image.example.com/badges/Punch_Count_5000.png",
     *              "badge_value": 1,
     *              "awarded": true,
     *              "count": 1,
     *              "shared": false
     *          },
     *          {
     *              "achievement_id": 12,
     *              "achievement_name": "Iron First",
     *              "name": "Gold",
     *              "description": "Iron First",
     *              "image": "http://image.example.com/badges/Iron_First.png",
     *              "badge_value": 1,
     *              "awarded": true,
     *              "count": 1,
     *              "shared": false
     *          }]
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function getClient($clientId)
    {
        $clientId = (int)$clientId;

        // $clientData = Client::with(['photo_url', 'preferences', 'country', 'state', 'city'])->find($clientId);
        $clientData = Client::with(['preferences', 'country', 'state', 'city'])->find($clientId);
        $clientData = $clientData->toArray();

        // Validation
        if (!$clientId || !$clientData) {
            return response()->json([
                'error' => 'false',
                'message' => 'Invalid request or client not found',
            ]);
        }

        $clientPoints = Client::select('id as points')->where('id', $clientId)->pluck('points')->first();
        $clientData['points'] = (int)$clientPoints;

        $leaderboard = Leaderboard::where('client_id', $clientId)->first();

        //$data = $this->getAvgSpeedAndForce($clientId);

        //$client = array_merge($clientData, $data);
        if (!empty($leaderboard->total_time_trained))
            $avgCount = $leaderboard->punches_count * 1000 * 60 / $leaderboard->total_time_trained;
        else
            $avgCount = 0;

        $data = array();

        if (!empty($leaderboard)) {
            if (!empty($leaderboard->total_time_trained))
                $totalTimeTrained = floor($leaderboard->total_time_trained / 1000);
            else
                $totalTimeTrained = 0;
            $data['total_time_trained'] = $totalTimeTrained;

            $data['total_day_trained'] = floor($leaderboard->total_days_trained);
            $data['avg_count'] = floor($avgCount);
            $data['avg_speed'] = floor($leaderboard->avg_speed);
            $data['avg_force'] = floor($leaderboard->avg_force);
        } else {
            $data['total_time_trained'] = 0;
            $data['total_day_trained'] = 0;
            $data['avg_count'] = 0;
            $data['avg_speed'] = 0;
            $data['avg_force'] = 0;
        }
        
        // \Log::info("client data -- data");
        // \Log::info($clientData);
        // \Log::info($data);
        $client = array_merge($clientData, $data);

        if (!empty($leaderboard->punches_count))
            $punchesCount = $leaderboard->punches_count;
        else
            $punchesCount = 0;

        $client['punches_count'] = $punchesCount;


        //$battles = Battles::getFinishedBattles($clientId);

        // $won = Battles::where('winner_client_id', $clientId)->count();
        // $lost = Battles::where(function ($query) use ($clientId) {
        //         $query->where('client_id', $clientId)->orWhere('opponent_client_id', $clientId);
        //     })
        //     ->where('winner_client_id', '!=', $clientId)->count();
        $won = 0;
        $lost = 0;

        $client['lose_counts'] = $lost;
        $client['win_counts'] = $won;
        //$client['finished_battles'] = $battles['finished'];

        if (!$client) {
            return response()->json(['error' => 'true', 'message' => 'client not found']);
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'client' => $client
        ]);
    }

    private function getAvgSpeedAndForce($clientId)
    {
        $session = ClientSessions::select('id', 'start_time', 'end_time')
            ->where('client_id', $clientId)
            ->where(function ($query) {
                $query->whereNull('battle_id')->orWhere('battle_id', '0');
            })->get()->toArray();
        $sessionIds = array_column($session, 'id');

        $totalTime = 0;
        $startDate = [];
        foreach ($session as $time) {
            if ($time['start_time'] > 0 && $time['end_time'] > 0 && $time['end_time'] > $time['start_time']) {
                $totalTime = $totalTime + abs($time['end_time'] - $time['start_time']);
                $startDate[] = date('y-m-d', (int)($time['start_time'] / 1000));
            }
        }

        $getAvgSession = ClientSessions::select(
                \DB::raw('AVG(avg_speed) as avg_speeds'),
                \DB::raw('AVG(avg_force) as avg_forces'),
                \DB::raw('MAX(punches_count) as avg_punch')
            )
            ->where('_id', $clientId)->where(function ($query) {
                $query->whereNull('battle_id')->orWhere('battle_id', '0');
            })->first();

        $avgCount = 0;
        $getAvgCount = SessionRounds::select(
                \DB::raw('SUM(ABS(start_time - end_time)) AS `total_time`'),
                \DB::raw('SUM(punches_count) as punches')
            )
            ->where('start_time', '>', 0)
            ->where('end_time', '>', 0)
            ->whereIn('session_id', $sessionIds)->first();
        if ($getAvgCount->total_time > 0) {
            $avgCount = $getAvgCount->punches * 1000 * 60 / $getAvgCount->total_time;
        }

        $data['total_time_trained'] = floor($totalTime / 1000);
        $data['total_day_trained'] = floor(count(array_unique($startDate)));
        $data['avg_count'] = floor($avgCount);
        $data['avg_speed'] = floor($getAvgSession->avg_speeds);
        $data['avg_force'] = floor($getAvgSession->avg_forces);

        return $data;
    }

    /**
     * @api {post} /coach/client/preferences Update client's preferences
     * @apiGroup Coach
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Boolean} [public_profile] Profile show public
     * @apiParam {Boolean} [show_achivements] Show achivements on to public profile or not
     * @apiParam {Boolean} [show_training_stats] Show training statistics on to public profile or not
     * @apiParam {Boolean} [show_challenges_history] Show challenges history on to public profile or not
     * @apiParam {Boolean} [badge_notification] Badge notification
     * @apiParam {Boolean} [show_tutorial] Show Tutorials
     * @apiParamExample {json} Input
     *    {
     *      "client_id": 54,
     *      "public_profile": true,
     *      "badge_notification": true
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Preferences have been saved",
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function updatePreferences(Request $request)
    {
        $client = Client::find($request->get('client_id'));
        $clientPreferences = $client->preferences;

        $publicProfile = filter_var($request->get('public_profile'), FILTER_VALIDATE_BOOLEAN);
        $clientPreferences->public_profile = $request->get('public_profile') ? $publicProfile : $clientPreferences->public_profile;

        $showAchivements = filter_var($request->get('show_achivements'), FILTER_VALIDATE_BOOLEAN);
        $clientPreferences->show_achivements = $request->get('show_achivements') ? $showAchivements : $clientPreferences->show_achivements;

        $showTrainingStats = filter_var($request->get('show_training_stats'), FILTER_VALIDATE_BOOLEAN);
        $clientPreferences->show_training_stats = $request->get('show_training_stats') ? $showTrainingStats : $clientPreferences->show_training_stats;

        $showChallengesHistory = filter_var($request->get('show_challenges_history'), FILTER_VALIDATE_BOOLEAN);
        $clientPreferences->show_challenges_history = $request->get('show_challenges_history') ? $showChallengesHistory : $clientPreferences->show_challenges_history;

        $badgeNotification = filter_var($request->get('badge_notification'), FILTER_VALIDATE_BOOLEAN);
        $clientPreferences->badge_notification = $request->get('badge_notification') ? $badgeNotification : $clientPreferences->badge_notification;

        $showTutorial = filter_var($request->get('show_tutorial'), FILTER_VALIDATE_BOOLEAN);
        $clientPreferences->show_tutorial = $request->get('show_tutorial') ? $showTutorial : $clientPreferences->show_tutorial;

        if (null !== $request->get('unit')) {
            $unit = filter_var($request->get('unit'), FILTER_VALIDATE_INT);
            $clientPreferences->unit = $request->get('unit');
        }

        $clientPreferences->save();

        return response()->json([
            'error' => 'false',
            'message' => 'Preferences have been saved',
        ]);
    }

}
