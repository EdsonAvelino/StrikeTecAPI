<?php

namespace App\Http\Controllers;

use App\User;
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
     *          "client": {
     *              "id": 1,
     *              "facebook_id": null,
     *              "first_name": "John",
     *              "last_name": "Smith",
     *              "email": "",
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
     *              "is_client": 1,
     *              "coach_user": 367
     *              "skill_level": "PRO",
     *              "photo_url": "",
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
     *              "points": 2500,
     *              "subscription": {
     *                  "trainee_monthly ": false,
     *                  "trainee_yearly ": false,
     *                  "coach_monthly ": false,
     *                  "spectator_monthly ": true,
     *                  "spectator_yearly ": false
     *              },
     *              "subscription_check": false
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
            'is_coach' => 0,
            'is_client' => 1,
            'coach_user' => \Auth::id(),
            'show_tip' => 1,
            'is_spectator' => 1,
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
            $client = User::create($newClient);

            $data = self::getClient($client->id);

            return response()->json([
                'error' => 'false',
                'message' => 'Client has been added successfully',
                'client' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => $e->getMessage()
            ]);
        }
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
     *              "id": 1,
     *              "facebook_id": null,
     *              "first_name": "John",
     *              "last_name": "Smith",
     *              "email": "",
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
     *              "is_client": 1,
     *              "coach_user": 464,
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
     *             "user_connections": 4,
     *             "achievements": []
     *          }
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

        $_clients = User::select('id', 'first_name', 'last_name')->where('coach_user', \Auth::id());

        if ($query) {
            $_clients->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%$query%")->orWhere('last_name', 'LIKE', "%$query%");
            });
        }
        
        $clients = $_clients->offset($offset)->limit($limit)->get();
        
        $data = [];
        foreach ($clients as $client) {
            $data[] = self::getClient($client['id']);
        }
        $data = collect($data)->sortBy('punches_count')->reverse()->toArray();
        
        // \Log::info(print_r($data, true));
        // \Log::info(print_r(array_keys($data), true));

        $results = [];
        foreach (array_keys($data) as $key => $index) {
            $data[$index]['rank'] = $key + 1;
            $results[$key] = $data[$index];
        }

        // \Log::info(print_r($results, true));
        
        return response()->json(['error' => 'false', 'message' => '', 'data' => $results]);
    }

    private function getClient($clientId)
    {
        $userId = (int)$clientId;

        $userData = User::with(['preferences', 'country', 'state', 'city'])->find($userId);

        // Validation
        if (!$userId || !$userData) {
            return null;
        }

        $userData = $userData->toArray();

        $userPoints = User::select('id as points')->where('id', $userId)->pluck('points')->first();
        $userData['points'] = (int)$userPoints;

        $leaderboard = \App\Leaderboard::where('user_id', $userId)->first();

        //$data = $this->getAvgSpeedAndForce($userId);

        //$user = array_merge($userData, $data);
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

        $user = array_merge($userData, $data);

        if (!empty($leaderboard->punches_count))
            $punchesCount = $leaderboard->punches_count;
        else
            $punchesCount = 0;

        $user['punches_count'] = $punchesCount;

        //$battles = Battles::getFinishedBattles($userId);

        $won = \App\Battles::where('winner_user_id', $userId)->count();
        $lost = \App\Battles::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhere('opponent_user_id', $userId);
            })
            ->where('winner_user_id', '!=', $userId)->count();

        $user['lose_counts'] = $lost;
        $user['win_counts'] = $won;
        //$user['finished_battles'] = $battles['finished'];

        $userFollowing = 'SELECT follow_user_id FROM user_connections WHERE user_id = ?';
        $connections = \App\UserConnections::where('follow_user_id', $userId)
            ->whereRaw("user_id IN ($userFollowing)", [$userId])
            ->count();
        $user['user_connections'] = $connections;
        //User Achievements data
        $achievementsArr = \App\UserAchievements::getUsersAchievements($userId);
        if (count($achievementsArr) > 3) {
            $user['achievements'] = array_slice($achievementsArr, 0, 3);
        } else {
            $user['achievements'] = $achievementsArr;
        }

        return $user;
    }

}
