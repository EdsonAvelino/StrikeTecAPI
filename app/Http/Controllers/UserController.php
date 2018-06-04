<?php

namespace App\Http\Controllers;

use Validator;
use App\User;
use App\UserConnections;
use App\UserSubscriptions;
use App\Faqs;
use App\Leaderboard;
use App\Battles;
use App\Sessions;
use App\SessionRounds;
use App\SessionRoundPunches;
use App\UserAchievements;
use App\UserNotifications;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class UserController extends Controller
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
     * @api {post} /user/register Register a new user
     * @apiGroup Users
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {String} first_name First Name of user
     * @apiParam {String} last_name Last Name of user
     * @apiParam {String} email Email
     * @apiParam {String} password Password
     * @apiParamExample {json} Input
     *    {
     *      "first_name": "John",
     *      "last_name": "Smith",
     *      "email": "john@smith.com",
     *      "password": "Something123"
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {String} token Access token
     * @apiSuccess {Object} user User object contains user's all information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Authentication successful",
     *      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *      "user": {
     *          "id": 1,
     *          "facebook_id": null,
     *          "first_name": "John",
     *          "last_name": "Smith",
     *          "email": "john@smith.com",
     *          "gender": null,
     *          "birthday": "1975-05-09",
     *          "weight": null,
     *          "height_feet": 5,
     *          "height_inches": 8,
     *          "left_hand_sensor": null,
     *          "right_hand_sensor": null,
     *          "left_kick_sensor": null,
     *          "right_kick_sensor": null,
     *          "is_spectator": 0,
     *          "stance": null,
     *          "show_tip": 1,
     *          "skill_level": "PRO",
     *          "photo_url": "http://image.example.com/profile/pic.jpg",
     *          "updated_at": "2016-02-10 15:46:51",
     *          "created_at": "2016-02-10 15:46:51",
     *          "preferences": {
     *              "public_profile": 0,
     *              "show_achivements": 1,
     *              "show_training_stats": 1,
     *              "show_challenges_history": 1
     *          },
     *          "country": {
     *              "id": 14,
     *              "name": "Austria"
     *          },
     *          "state": {
     *              "id": 286,
     *              "country_id": 14,
     *              "name": "Oberosterreich"
     *          },
     *          "city": {
     *              "id": 6997,
     *              "state_id": 286,
     *              "name": "Pettenbach"
     *          },
     *          "points": 0
     *      }
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
                    'email' => 'required|max:64|unique:users',
                        // 'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*+_-])(?=.*\d)[A-Za-z0-9~!@#$%^&*+_-]{8,}$/',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors->first('email')]);
        }

        // Creates a new user
        $user = User::create([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'email' => $request->get('email'),
            'password' => app('hash')->make($request->get('password')),
            'show_tip' => 1,
            'is_spectator' => 1
        ]);

        try {
            if (!$token = $this->jwt->attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'true', 'message' => 'Invalid request']);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token has been expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'true', 'message' => 'Invalid token'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token does not exists'], $e->getStatusCode());
        }

        $user = User::with(['preferences', 'country', 'state', 'city'])->find(\Auth::id())->toArray();

        $userPoints = User::select('id as points')->where('id', $user['id'])->pluck('points')->first();
        $user['points'] = (int) $userPoints;

        return response()->json(['error' => 'false', 'message' => 'Registration successful', 'token' => $token, 'user' => $user]);
    }

    /**
     * @api {post} /user/register/facebook Signup with Facebook
     * @apiGroup Facebook Auth
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {String} facebook_id Facebook ID from facebook response
     * @apiParam {String} first_name First Name from facebook response
     * @apiParam {String} last_name Last Name from facebook response
     * @apiParam {String} email Email from facebook response
     * @apiParamExample {json} Input
     *    {
     *      "facebook_id": "1234567890",
     *      "first_name": "John",
     *      "last_name": "Smith",
     *      "email": "john@smith.com",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {String} token Access token
     * @apiSuccess {Object} user User object contains user's all information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Facebook registration successful",
     *      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *      "user": {
     *          "id": 1,
     *          "facebook_id": 1234567890,
     *          "first_name": "John",
     *          "last_name": "Smith",
     *          "email": "john@smith.com",
     *          "gender": null,
     *          "birthday": "1975-05-09",
     *          "weight": null,
     *          "height_feet": 5,
     *          "height_inches": 11,
     *          "left_hand_sensor": null,
     *          "right_hand_sensor": null,
     *          "left_kick_sensor": null,
     *          "right_kick_sensor": null,
     *          "is_spectator": 0,
     *          "stance": null,
     *          "show_tip": 1,
     *          "skill_level": "PRO",
     *          "photo_url": "http://image.example.com/profile/pic.jpg",
     *          "updated_at": "2016-02-10 15:46:51",
     *          "created_at": "2016-02-10 15:46:51",
     *          "preferences": {
     *              "public_profile": 0,
     *              "show_achivements": 1,
     *              "show_training_stats": 1,
     *              "show_challenges_history": 1
     *          },
     *          "country": {
     *              "id": 14,
     *              "name": "Austria"
     *          },
     *          "state": {
     *              "id": 286,
     *              "country_id": 14,
     *              "name": "Oberosterreich"
     *          },
     *          "city": {
     *              "id": 6997,
     *              "state_id": 286,
     *              "name": "Pettenbach"
     *          },
     *          "points": 0
     *      }
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid credentials or user is not registered"
     *      }
     * @apiVersion 1.0.0
     */
    public function registerFacebook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facebook_id' => 'required|unique:users,facebook_id',
            'email' => 'required|unique:users,email',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->get('facebook_id'))
                return response()->json(['error' => 'true', 'message' => 'User already registered']);
            elseif ($errors->get('email'))
                return response()->json(['error' => 'true', 'message' => 'Email already registered']);
        }

        $user = User::create(['facebook_id' => $request->get('facebook_id'),
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'email' => $request->get('email'),
            'password' => app('hash')->make(strrev($request->get('facebook_id'))),
            'show_tip' => 1,
            'is_spectator' => 1
        ]);

        try {
            if (!$token = $this->jwt->attempt(['email' => $user->email,
                'password' => strrev($request->get('facebook_id'))])) {
                return response()->json(['error' => 'true', 'message' => 'Invalid request']);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token has been expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'true', 'message' => 'Invalid token'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token does not exists'], $e->getStatusCode());
        }

        $user = User::with(['preferences', 'country', 'state', 'city'])->find(\Auth::id())->toArray();

        $userPoints = User::select('id as points')->where('id', $user['id'])->pluck('points')->first();
        $user['points'] = (int) $userPoints;

        return response()->json(['error' => 'false', 'message' => 'Facebook registration successful', 'token' => $token, 'user' => $user]);
    }

    /**
     * @api {post} /users Update a user
     * @apiGroup Users
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} [first_name] First Name
     * @apiParam {String} [last_name] Last Name
     * @apiParam {String="male","female"} [gender] Gender
     * @apiParam {Date} [birthday] Birthday in MM-DD-YYYY e.g. 09/11/1987
     * @apiParam {Number} [weight] Weight
     * @apiParam {Number} [height_feet] Height (Feet Value)
     * @apiParam {Number} [height_inches] Height (Inches Value)
     * @apiParam {Boolean} [is_spectator] Spectator true / false
     * @apiParam {String} [stance] Stance
     * @apiParam {Boolean} [show_tip] Show tips true / false
     * @apiParam {String} [skill_level] Skill level of user
     * @apiParam {String} [photo_url] User profile photo-url
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
     *          "message": "User details have been updated successfully"
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'gender' => 'nullable|in:male,female',
            'birthday' => 'nullable|date',
        ]);

        try {
            $user = \Auth::user();

            $user->first_name = ($request->get('first_name')) ?? $user->first_name;
            $user->last_name = ($request->get('last_name')) ?? $user->last_name;
            $user->gender = ($request->get('gender')) ?? $user->gender;

            $birthday = $request->get('birthday') ?
                    date('Y-m-d', strtotime($request->get('birthday'))) :
                    $user->birthday;
            $user->birthday = $birthday;

            $user->weight = $request->get('weight') ?? $user->weight;
            
            $user->height_feet = $request->get('height_feet') ?? $user->height_feet;
            $user->height_inches = $request->get('height_inches') ?? $user->height_inches;

            $isSpectator = filter_var($request->get('is_spectator'), FILTER_VALIDATE_BOOLEAN);
            $user->is_spectator = $request->get('is_spectator') ? $isSpectator : $user->is_spectator;

            $showTip = filter_var($request->get('show_tip'), FILTER_VALIDATE_BOOLEAN);
            $user->show_tip = $request->get('show_tip') ? $showTip : $user->show_tip;

            $user->skill_level = $request->get('skill_level') ?? $user->skill_level;
            $user->stance = $request->get('stance') ?? $user->stance;
            $user->photo_url = $request->get('photo_url') ?? $user->photo_url;

            $user->city_id = $request->get('city_id') ?? $user->city_id;
            $user->state_id = $request->get('state_id') ?? $user->state_id;
            $user->country_id = $request->get('country_id') ?? $user->country_id;

            $user->save();

            return response()->json([
                        'error' => 'false',
                        'message' => 'User details have been updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'true',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @api {post} /users/sensors Update user's sensor
     * @apiGroup Users
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
        // Find user who have shared his sensors
        $leftHandSensor = $request->get('left_hand_sensor');
        $rightHandSensor = $request->get('right_hand_sensor');

        $_user = User::select('id', 'is_sharing_sensors')->where(function($query) use ($leftHandSensor) {
            $query->where('left_hand_sensor', $leftHandSensor)->orWhere('right_hand_sensor', $leftHandSensor);
        })->where(function($query) use ($rightHandSensor) {
            $query->where('left_hand_sensor', $rightHandSensor)->orWhere('right_hand_sensor', $rightHandSensor);
        });

        // In case, user exists with requested mac address of sensors and sharing sensors
        // then no need to store into db, just success response
        if ( $_user->exists() && (($_user = $_user->first())->is_sharing_sensors) ) {
            return response()->json([
                'error' => 'false',
                'message' => 'Updated successfully'
            ]);
        }

        $validator = Validator::make($request->all(), [
                    'left_hand_sensor' => 'nullable|unique:users,left_hand_sensor,'.\Auth::id().'|unique:users,right_hand_sensor,'.\Auth::id(),
                    'right_hand_sensor' => 'nullable|unique:users,right_hand_sensor,'.\Auth::id().'|unique:users,left_hand_sensor,'.\Auth::id(),
                    'left_kick_sensor' => 'nullable|unique:users,left_kick_sensor,'.\Auth::id().'|unique:users,right_kick_sensor,'.\Auth::id(),
                    'right_kick_sensor' => 'nullable|unique:users,right_kick_sensor,'.\Auth::id().'|unique:users,left_kick_sensor,'.\Auth::id(),
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
            $user = \Auth::user();

            $user->left_hand_sensor = ($request->get('left_hand_sensor')) ?? $user->left_hand_sensor;
            $user->right_hand_sensor = ($request->get('right_hand_sensor')) ?? $user->right_hand_sensor;
            $user->left_kick_sensor = ($request->get('left_kick_sensor')) ?? $user->left_kick_sensor;
            $user->right_kick_sensor = ($request->get('right_kick_sensor')) ?? $user->right_kick_sensor;
            
            $user->is_spectator = 0;

            $user->save();

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
     * @api {get} /users/search Search users
     * @apiGroup Users
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} query Search term e.g. "jo"
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of users
     * @apiParamExample {json} Input
     *    {
     *      "query": "jo",
     *      "start": 0,
     *      "limit": 10,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} users List of users followed by search term
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *              {
     *                  "id": 163,
     *                  "first_name": "Domingo",
     *                  "last_name": "Suthworth",
     *                  "photo_url": null,
     *                  "gender": "male",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 470
     *              },
     *              {
     *                  "id": 241,
     *                  "first_name": "Giraud",
     *                  "last_name": "Dorrington",
     *                  "photo_url": null,
     *                  "gender": "male",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 960
     *              },
     *              {
     *                  "id": 281,
     *                  "first_name": "Donny",
     *                  "last_name": "Stanlick",
     *                  "photo_url": null,
     *                  "gender": "female",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 950
     *              },
     *              {
     *                  "id": 318,
     *                  "first_name": "Wilburt",
     *                  "last_name": "Dorgon",
     *                  "photo_url": null,
     *                  "gender": "male",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 980
     *              },
     *              {
     *                  "id": 384,
     *                  "first_name": "Ricky",
     *                  "last_name": "Douce",
     *                  "photo_url": null,
     *                  "gender": "female",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 590
     *              },
     *              {
     *                  "id": 500,
     *                  "first_name": "Dotty",
     *                  "last_name": "Matuska",
     *                  "photo_url": null,
     *                  "gender": "female",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 550
     *              },
     *              {
     *                  "id": 654,
     *                  "first_name": "Sharia",
     *                  "last_name": "Dooly",
     *                  "photo_url": null,
     *                  "gender": "female",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 650
     *              },
     *              {
     *                  "id": 811,
     *                  "first_name": "Joshia",
     *                  "last_name": "Dolby",
     *                  "photo_url": null,
     *                  "gender": "male",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 580
     *              },
     *              {
     *                  "id": 872,
     *                  "first_name": "Dorthea",
     *                  "last_name": "Tidey",
     *                  "photo_url": null,
     *                  "gender": "female",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 120
     *              },
     *              {
     *                  "id": 936,
     *                  "first_name": "Donal",
     *                  "last_name": "Dallimare",
     *                  "photo_url": null,
     *                  "gender": "male",
     *                  "user_following": false,
     *                  "user_follower": false,
     *                  "points": 190
     *              }
     *          ]
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function searchUsers(Request $request)
    {
        $query = trim($request->get('query'));
        
        if (!$query) {
            return response()->json([
                'error' => 'true',
                'message' => 'Nothing requested',
            ]);
        }

        $name = str_replace('+', ' ', $request->get('query'));

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        @list($firstname, $lastname) = explode(' ', $name);

        $_users = User::select([
                    'id',
                    'first_name',
                    'last_name',
                    'photo_url',
                    'gender',
                    \DB::raw('id as user_following'),
                    \DB::raw('id as user_follower'),
                    \DB::raw('id as points')
                ])->where('id', '<>', \Auth::id())->offset($offset)->limit($limit);

        if (!empty($firstname) && !empty($lastname)) {
            $_users->where('first_name', 'like', "%$firstname%")->where('last_name', 'like', "%$lastname%");
        } elseif (!empty($name)) {
            $_users->where(function($query) use ($name) {
                $query->where('first_name', 'like', "%$name%")->orWhere('last_name', 'like', "%$name%");
            });
        }

        $users = $_users->get(); 

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $users
        ]);
    }

    /**
     * Alter param
     */
    private function alterParam(&$param)
    {
        $param = "%$param%";
    }

    /**
     * @api {get} /users/score Get user's score
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
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} users List of users followed by search term
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
    public function getUsersGameScores(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'game_id'    => 'required|exists:games,id',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('game_id')]);
        }

        $gameId = (int) $request->get('game_id');

        $leaderboardData = \App\GameLeaderboard::select('game_id', 'score', 'distance')->where('user_id', \Auth::id())->where('game_id', $gameId)->first();

        $data = new \stdClass;

        if ($leaderboardData) {
            $score = $leaderboardData->score;

            switch ($leaderboardData->game_id) {
                case 1: $score = (float) number_format($score, 3); break; // Reaction time
                case 2: $score = (int) $score; break;
                case 3: $score = (int) $score; break;
                case 4: $score = (int) $score; break;
            }

            $data->score = $score;
            $data->distance = (float) number_format($leaderboardData->distance, 1) ;
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $data
        ]);
    }

    /**
     * @api {get} /users/progress Get user's training progress
     * @apiGroup Users
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
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
    public function getUsersProgress(Request $request)
    {
        $totalCombos = \App\ComboTags::select('filter_id', \DB::raw('COUNT(combo_id) as combos_count'))->groupBy('filter_id')->get();

        $result = [];

        foreach ($totalCombos as $row) {
            $combos = \App\ComboTags::select('combo_id')->where('filter_id', $row->filter_id)->get()->pluck('combo_id')->toArray();

            $userTrained = \App\Sessions::select('plan_id', \DB::raw('COUNT(id) as total'))
                ->where('user_id', \Auth::id())->where('type_id', \App\Types::COMBO)
                ->whereIn('plan_id', $combos)
                // ->whereRaw('YEARWEEK(FROM_UNIXTIME(start_time / 1000), 1) = YEARWEEK(CURDATE(), 1)')
                ->groupBy('plan_id')->get()->count();

            $result[$row->filter->filter_name] = ['trained' => $userTrained, 'total' => $row->combos_count];
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $result
        ]);
    }

    /**
     * @api {post} /users/subscription User's IAP app subscription
     * @apiGroup In-App Purchases
     * @apiHeader {String} authorization Authorization value
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {String="IOS","ANDROID"} platform App Platform iOS or Android
     * @apiParam {Json} receipt Receipt object
     * @apiParamExample {json} Input
     *    {
     *      'platform' : 'android',
     *      'receipt': '{"orderId":"GPA.3343-1595-7351-65476","packageName":"efd.com.strikesub","productId":"trainee_yearly_399","purchaseTime":1527181738040,"purchaseState":0,"developerPayload":"33","purchaseToken":"iopahmdkggnddjiidkhpnggd.AO-..._4mR-KelXb6XpLlyOWBQ4SgLcZX780BBHVuaQlOVaCcGdN0QnyPvuIFOiLTgy4cRjH50ulPTUpkg","autoRenewing":true}',
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Subscribed",
     *      }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function postUserSubscription(Request $request)
    {
        // receipt
        // $receipt = '{"orderId":"GPA.3343-1595-7351-65476","packageName":"efd.com.strikesub","productId":"trainee_yearly_399","purchaseTime":1527181738040,"purchaseState":0,"developerPayload":"33","purchaseToken":"iopahmdkggnddjiidkhpnggd.AO-J1Owfm38NMtFGkf-hesSoA6WI-ssf964HIgthX5qQkPp5webNpO2hUwNXUmAL_4mR-KelXb6XpLlyOWBQ4SgLcZX780BBHVuaQlOVaCcGdN0QnyPvuIFOiLTgy4cRjH50ulPTUpkg","autoRenewing":true}';

        if (null == ($request->get('receipt'))) {
            return response()->json(['error' => 'true', 'message' => 'Missing data']);
        }

        $receipt = json_decode($request->get('receipt'));
        
        $IAPproduct = \App\IapProducts::where('product_id', $receipt->productId)->where('platform', $request->get('platform'))->first();

        if ( !$IAPproduct ) {
            return response()->json(['error' => 'true', 'message' => 'Invalid data, product detail not found']);
        }

        // Fetch user's existing subscription
        $subscription = UserSubscriptions::where('user_id', \Auth::id())->first();

        // Calculate expire time
        $purchaseTime = $receipt->purchaseTime / 1000;

        switch($receipt->productId) {
            // Yearly - Prod
            case 'striketec_coach_month':
            case 'striketec_spectator_month':
            case 'striketec_trainee_month':
            // Yearly - Dev
            case 'trainee_month_399':
            case 'coach_399':
            case 'spectator_monthly_399':
                $expireAt = strtotime(date("Y-m-d h:i:s", $purchaseTime) . " +1 month");
                break;
            
            // Monthly - Prod
            case 'striketec_spectator_year':
            case 'striketec_trainee_year':
            // Monthly - Dev
            case 'trainee_yearly_399':
            case 'spectator_yearly_399':
                $expireAt = strtotime(date("Y-m-d h:i:s", $purchaseTime) . " +12 month");
                break;
        }


        if ( !$subscription ) {
            // Creates new if not found
            $subscription = UserSubscriptions::create([
                'user_id' => \Auth::id(),
                'iap_product_id' => $IAPproduct->id,
                'platform' => $request->get('platform'),
                'receipt' => $request->get('receipt'),
                'purchased_at' => $purchaseTime,
                'expire_at' => $expireAt
            ]);
        } else {
            // Updates existing subscription
            $subscription->iap_product_id = $IAPproduct->id;
            $subscription->platform = $request->get('platform');
            $subscription->receipt = $request->get('receipt');            
            $subscription->purchased_at = $purchaseTime;
            $subscription->expire_at = $expireAt;

            $subscription->save();
        }

        return response()->json(['error' => 'false', 'message' => 'Subscribed']);
    }

    /**
     * @api {get} /users/<user_id> Get user information
     * @apiGroup Users
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {number} [user_id] User's ID, if not given it will give current logged in user's info

     * @apiParamExample {json} Input
     *    {
     *      "user_id": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} user User's information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "user": {
     *              "id": 1,
     *              "facebook_id": 1234567890,
     *              "first_name": "John",
     *              "last_name": "Smith",
     *              "email": "john@smith.com",
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
     *              "skill_level": null,
     *              "photo_url": "http://image.example.com/profile/pic.jpg",
     *              "updated_at": "2016-02-10 15:46:51",
     *              "created_at": "2016-02-10 15:46:51",
     *              "followers_count": 0,
     *              "following_count": 0,
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
     *             "user_following": true,
     *             "user_follower": false,
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
     *                         "user_following": true,
     *                         "user_follower": true,
     *                         "points": 323
     *                     },
     *                     "loser": {
     *                         "id": 7,
     *                         "first_name": "Qiang",
     *                         "last_name": "Hu",
     *                         "photo_url": "http://image.example.com/profileImages/sub-1509460359.png",
     *                         "user_following": false,
     *                         "user_follower": false,
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
     *                         "user_following": false,
     *                         "user_follower": false,
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
     *                         "user_following": false,
     *                         "user_follower": false,
     *                         "points": 5854
     *                     },
     *                     "loser": {
     *                         "id": 1,
     *                         "first_name": "Nawaz",
     *                         "last_name": "Me",
     *                         "photo_url": null,
     *                         "user_following": true,
     *                         "user_follower": true,
     *                         "points": 2768
     *                     }
     *                 }
     *             ],
     *           "user_connections": 4,
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
    public function getUser($userId)
    {
        $userId = (int) $userId;
        
        $userData = User::with(['preferences', 'country', 'state', 'city'])->withCount('followers')->withCount('following')->find($userId);

        // Validation
        if (!$userId || !$userData) {
            return response()->json([
                'error' => 'false',
                'message' => 'Invalid request or user not found',
            ]);
        }

        // user_following = current user is following this user
        $userFollowing = UserConnections::where('follow_user_id', $userId)
                        ->where('user_id', \Auth::user()->id)->exists();

        // user_follower = this user if following current user
        $userFollower = UserConnections::where('user_id', $userId)
                        ->where('follow_user_id', \Auth::user()->id)->exists();

        $userData = $userData->toArray();
        $userData['user_following'] = (bool) $userFollowing;
        $userData['user_follower'] = (bool) $userFollower;

        $userPoints = User::select('id as points')->where('id', $userId)->pluck('points')->first();
        $userData['points'] = (int) $userPoints;

        $leaderboard = Leaderboard::where('user_id', $userId)->first();
        $data = $this->getAvgSpeedAndForce($userId);
        $user = array_merge($userData, $data);

        $user['punches_count'] = $leaderboard->punches_count;

        $battles = Battles::getFinishedBattles($userId);

        $user['lose_counts'] = $battles['lost'];
        $user['win_counts'] = $battles['won'];
        $user['finished_battles'] = $battles['finished'];

        $userFollowing = 'SELECT follow_user_id FROM user_connections WHERE user_id = ?';
        $connections = UserConnections::where('follow_user_id', $userId)
                ->whereRaw("user_id IN ($userFollowing)", [$userId])
                ->count();
        $user['user_connections'] = $connections;
        //User Achievements data
        $achievementsArr = UserAchievements::getUsersAchievements($userId);
        if (count($achievementsArr) > 3) {
            $user['achievements'] = array_slice($achievementsArr, 0, 3);
        } else {
            $user['achievements'] = $achievementsArr;
        }
        if (!$user) {
            return response()->json(['error' => 'true', 'message' => 'User not found']);
        }

        return response()->json([
                    'error' => 'false',
                    'message' => '',
                    'user' => $user
        ]);
    }

    /**
     * @api {post} /users/preferences Update user's preferences
     * @apiGroup Users
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
        $userId = \Auth::user()->id;

        $user = User::find($userId);
        $userPreferences = $user->preferences;

        $publicProfile = filter_var($request->get('public_profile'), FILTER_VALIDATE_BOOLEAN);
        $userPreferences->public_profile = $request->get('public_profile') ? $publicProfile : $userPreferences->public_profile;

        $showAchivements = filter_var($request->get('show_achivements'), FILTER_VALIDATE_BOOLEAN);
        $userPreferences->show_achivements = $request->get('show_achivements') ? $showAchivements : $userPreferences->show_achivements;

        $showTrainingStats = filter_var($request->get('show_training_stats'), FILTER_VALIDATE_BOOLEAN);
        $userPreferences->show_training_stats = $request->get('show_training_stats') ? $showTrainingStats : $userPreferences->show_training_stats;

        $showChallengesHistory = filter_var($request->get('show_challenges_history'), FILTER_VALIDATE_BOOLEAN);
        $userPreferences->show_challenges_history = $request->get('show_challenges_history') ? $showChallengesHistory : $userPreferences->show_challenges_history;

        $badgeNotification = filter_var($request->get('badge_notification'), FILTER_VALIDATE_BOOLEAN);
        $userPreferences->badge_notification = $request->get('badge_notification') ? $badgeNotification : $userPreferences->badge_notification;

        $showTutorial = filter_var($request->get('show_tutorial'), FILTER_VALIDATE_BOOLEAN);
        $userPreferences->show_tutorial = $request->get('show_tutorial') ? $showTutorial : $userPreferences->show_tutorial;
        
        // Unit
        
        $userPreferences->save();

        return response()->json([
            'error' => 'false',
            'message' => 'Preferences have been saved',
        ]);
    }

    /**
     * @api {get} /user/follow/<user_id> Follow other user
     * @apiGroup Social
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} user_id Follow user's ID
     * @apiParamExample {json} Input
     *    {
     *      "user_id": 9,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "User now following",
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function follow($userId = null)
    {
        if ($userId == \Auth::user()->id || !$userId)
            return null;

        $connection = UserConnections::where('user_id', \Auth::user()->id)
                        ->where('follow_user_id', $userId)->first();

        if (!$connection) {
            UserConnections::create([
                'user_id' => \Auth::user()->id,
                'follow_user_id' => $userId,
            ]);

            // Generates new notification for user
            UserNotifications::generate(UserNotifications::FOLLOW, $userId, \Auth::user()->id);

            $followUser = User::find($userId);

            return response()->json([
                'error' => 'false',
                'message' => 'User now following ' . $followUser->first_name . ' ' . $followUser->last_name,
            ]);
        }
    }

    /**
     * @api {get} /user/unfollow/<user_id> Unfollow user
     * @apiGroup Social
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} user_id Follow user's ID
     * @apiParamExample {json} Input
     *    {
     *      "user_id": 9,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Unfollow successfull",
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function unfollow($userId = null)
    {
        if ($userId == \Auth::user()->id || !$userId)
            return null;

        $connection = UserConnections::where('user_id', \Auth::user()->id)
                        ->where('follow_user_id', $userId)->delete();

        return response()->json([
                    'error' => 'false',
                    'message' => 'Unfollow successfull',
        ]);
    }

    /**
     * @api {get} /user/followers Get user's followers
     * @apiGroup Social
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains list of followers
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *          {
     *              "id": 5,
     *              "first_name": "Max",
     *              "last_name": "Zuck",
     *              "user_following": true,
     *              "user_follower": true,
     *              "points": 125,
     *              "photo_url": "http://image.example.com/u9384393030.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "user_following": false,
     *              "user_follower": false,
     *              "points": 130,
     *              "photo_url": "http://image.example.com/u93393989020.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 150,
     *              "photo_url": "http://image.example.com/u03948474839.jpg"
     *          },
     *          {
     *              "id": 9,
     *              "first_name": "Keily",
     *              "last_name": "Maxi",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 120,
     *              "photo_url": "http://image.example.com/u204948474839.jpg"
     *          }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function getFollowers(Request $request)
    {
        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $followers = UserConnections::where('follow_user_id', \Auth::user()->id)->offset($offset)->limit($limit)->get();

        $_followers = [];

        foreach ($followers as $follower) {
            $following = UserConnections::where('follow_user_id', $follower->user_id)
                            ->where('user_id', \Auth::user()->id)->exists();

            $follow = UserConnections::where('user_id', $follower->user_id)
                            ->where('follow_user_id', \Auth::user()->id)->exists();

            $leaderboard = Leaderboard::where('user_id', $follower->user_id)->first();
            $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

            $_followers[] = [
                'id' => $follower->user_id,
                'first_name' => $follower->user->first_name,
                'last_name' => $follower->user->last_name,
                'photo_url' => $follower->user->photo_url,
                'points' => (int) $points,
                'user_following' => (bool) $following,
                'user_follower' => (bool) $follow
            ];
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $_followers
        ]);
    }

    /**
     * @api {get} /user/<user_id>/followers Get followers of other user
     * @apiGroup Social
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains list of followers
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *          {
     *              "id": 5,
     *              "first_name": "Max",
     *              "last_name": "Zuck",
     *              "user_following": true,
     *              "user_follower": true,
     *              "points": 125,
     *              "photo_url": "http://image.example.com/u3874393848.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "user_following": false,
     *              "user_follower": false,
     *              "points": 130,
     *              "photo_url": "http://image.example.com/u94835748390.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 150,
     *              "photo_url": "http://image.example.com/u8847574839039.jpg"
     *          },
     *          {
     *              "id": 9,
     *              "first_name": "Keily",
     *              "last_name": "Maxi",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 120,
     *              "photo_url": "http://image.example.com/u93847475849.jpg"
     *          }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function getFollowersOfUser($userId, Request $request)
    {
        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $followers = UserConnections::where('follow_user_id', $userId)->offset($offset)->limit($limit)->get();

        $_followers = [];

        foreach ($followers as $follower) {
            $following = UserConnections::where('follow_user_id', $follower->user_id)
                            ->where('user_id', \Auth::user()->id)->exists();

            $follow = UserConnections::where('user_id', $follower->user_id)
                            ->where('follow_user_id', \Auth::user()->id)->exists();

            $leaderboard = Leaderboard::where('user_id', $follower->user_id)->first();
            $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

            $_followers[] = [
                'id' => $follower->user_id,
                'first_name' => $follower->user->first_name,
                'last_name' => $follower->user->last_name,
                'photo_url' => $follower->user->photo_url,
                'points' => (int) $points,
                'user_following' => (bool) $following,
                'user_follower' => (bool) $follow
            ];
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $_followers
        ]);
    }

    /**
     * @api {get} /user/following Get user's following
     * @apiGroup Social
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains list of followings
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *          {
     *              "id": 5,
     *              "first_name": "Max",
     *              "last_name": "Zuck",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 125,
     *              "photo_url": "http://image.example.com/u485758494.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 135,
     *              "photo_url": "http://image.example.com/u3955849404.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "user_following": false,
     *              "user_follower": true,
     *              "points": 140,
     *              "photo_url": "http://image.example.com/u9855748939.jpg"
     *          }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function getFollowing(Request $request)
    {
        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $following = UserConnections::where('user_id', \Auth::user()->id)->offset($offset)->limit($limit)->get();

        $_following = [];

        foreach ($following as $follower) {
            $following = UserConnections::where('follow_user_id', $follower->follow_user_id)
                            ->where('user_id', \Auth::user()->id)->exists();

            $follow = UserConnections::where('user_id', $follower->follow_user_id)
                            ->where('follow_user_id', \Auth::user()->id)->exists();

            $leaderboard = Leaderboard::where('user_id', $follower->follow_user_id)->first();
            $points = (!empty($leaderboard)) ? $leaderboard->punches_count : 0;

            $_following[] = [
                'id' => $follower->follow_user_id,
                'first_name' => $follower->followUser->first_name,
                'last_name' => $follower->followUser->last_name,
                'photo_url' => $follower->followUser->photo_url,
                'points' => (int) $points,
                'user_following' => (bool) $following,
                'user_follower' => (bool) $follow
            ];
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $_following
        ]);
    }

    /**
     * @api {get} /user/<user_id>/following Get following of other user
     * @apiGroup Social
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains list of followings
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *          {
     *              "id": 5,
     *              "first_name": "Max",
     *              "last_name": "Zuck",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 125,
     *              "photo_url": "http://image.example.com/u98457449495.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 135,
     *              "photo_url": "http://image.example.com/u9293833939.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "user_following": false,
     *              "user_follower": true,
     *              "points": 140,
     *              "photo_url": "http://image.example.com/u9498585940.jpg"
     *          }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function getFollowingOfUser($userId, Request $request)
    {
        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $following = UserConnections::where('user_id', $userId)->offset($offset)->limit($limit)->get();

        $_following = [];

        foreach ($following as $follower) {
            $user = User::select(
                    \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points')
                )->where('id', $follower->follow_user_id)->first();

            $_following[] = [
                'id' => $follower->follow_user_id,
                'first_name' => $follower->followUser->first_name,
                'last_name' => $follower->followUser->last_name,
                'photo_url' => $follower->followUser->photo_url,
                'points' => (int) $user->points,
                'user_following' => (bool) $user->user_following,
                'user_follower' => (bool) $user->user_follower
            ];
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $_following
        ]);
    }

    /**
     * @api {get} /user/follow/suggestions Get follow suggestions for current user
     * @apiGroup Social
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains list of suggested users to follow
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *          {
     *              "id": 5,
     *              "first_name": "Max",
     *              "last_name": "Zuck",
     *              "photo_url": "http://image.example.com/u85747383990.jpg",
     *              "points": 125
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "photo_url": "http://image.example.com/u4875748399.jpg",
     *              "points": 135
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "photo_url": "http://image.example.com/u84757883939.jpg",
     *              "points": 140
     *          }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getFollowSuggestions(Request $request)
    {
        // a) suggested users who are following current user
        // b) suggested users who are followed by user whom current user is following. of course, current user is not following returned users.

        // incase of user is newly registered, suggest trending users

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $currentUserFollowing = 'SELECT follow_user_id FROM user_connections WHERE user_id = ?';

        $suggested1 = \DB::table('user_connections')->select('user_id')->where('follow_user_id', \Auth::user()->id)->whereRaw("user_id NOT IN ($currentUserFollowing)", [\Auth::user()->id]);

        $suggestedUsersQuery = \DB::table('user_connections')->select('follow_user_id as user_id')
                ->whereRaw("user_id IN ($currentUserFollowing)", [\Auth::user()->id])
                ->where('follow_user_id', '!=', \Auth::user()->id)
                ->whereRaw("follow_user_id NOT IN ($currentUserFollowing)", [\Auth::user()->id])
                ->union($suggested1);

        $suggestedUsersCount = \DB::table(\DB::raw("({$suggestedUsersQuery->toSql()}) as raw"))
                        ->select('user_id')->mergeBindings($suggestedUsersQuery)->count();

        // TODO need to improve this suggestion of users to follow
        if ($suggestedUsersCount < 1) {
            $suggestedUsers = User::select('id as user_id')->where('country_id', \Auth::user()->country_id)->where('id', '!=', \Auth::id())->whereRaw("id NOT IN ($currentUserFollowing)", [\Auth::id()])->offset($offset)->limit($limit)->get();
        } else {
            $suggestedUsers = \DB::table(\DB::raw("({$suggestedUsersQuery->toSql()}) as raw"))
                        ->select('user_id')->mergeBindings($suggestedUsersQuery)
                        ->offset($offset)->limit($limit)->get();
        }

        $suggestedUsersIds = [];

        foreach ($suggestedUsers as $user) {
            $suggestedUsersIds[] = $user->user_id;
        }

        $users = User::get($suggestedUsersIds);

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $users
        ]);
    }

    /**
     * @api {get} /user/connections/<user_id> Get user's connections
     * @apiGroup Social
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} user_id User's Id
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParam {Boolean="true","false"} [spectator] Include Spectator users or not
     * @apiParamExample {json} Input
     *    {
     *      "user_id": 7,
     *      "start": 20,
     *      "limit": 50
     *      "spectator": true
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Array} data Data contains list of connections
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *          {
     *              "id": 5,
     *              "first_name": "Max",
     *              "last_name": "Zuck",
     *              "points": 125,
     *              "user_following": true,
     *              "user_follower": false,
     *              "photo_url": "http://image.example.com/u948474758.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "points": 135,
     *              "user_following": true,
     *              "user_follower": true,
     *              "photo_url": "http://image.example.com/u9983857579.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "points": 140,
     *              "user_following": false,
     *              "user_follower": true,
     *              "photo_url": "http://image.example.com/u5878494948.jpg"
     *          }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid data"
     *      }
     * @apiVersion 1.0.0
     */
    public function getConnections(Request $request, $userId)
    {
        $userId = (int) ($userId ?? \Auth::user()->id);

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $includeSpectators = filter_var($request->get('spectator'), FILTER_VALIDATE_BOOLEAN);

        $userFollowing = 'SELECT follow_user_id FROM user_connections WHERE user_id = ?';

        $connections = [];

        $_connections = UserConnections::where('follow_user_id', $userId)
                        ->whereRaw("user_id IN ($userFollowing)", [$userId]);
        
        if (!$includeSpectators) {
            $_connections->join('users', 'users.id', '=', 'user_connections.user_id');
            $_connections->where('users.is_spectator', '!=', 1);
        }

        $_connections = $_connections->offset($offset)->limit($limit)->get();

        foreach ($_connections as $connection) {
            $connections[] = User::get($connection->user_id);
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $connections
        ]);
    }

    /**
     * @api {post} /users/change_password Change user's password
     * @apiGroup Users
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Boolean} old_password Current password
     * @apiParam {Boolean} password New password to set
     * @apiParamExample {json} Input
     *    {
     *      "old_password": "Something123",
     *      "password": "NewPassword123",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Password changed successfully",
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid old password"
     *      }
     * @apiVersion 1.0.0
     */
    public function setUserPassword(Request $request)
    {
        // Get current user
        $user = \Auth::user();

        $oldPassword = $request->get('old_password');

        if (app('hash')->check($oldPassword, $user->password)) {
            $user->where('id', $user->id)->update(['password' => app('hash')->make($request->get('password'))]);

            return response()->json([
                'error' => 'false',
                'message' => 'Password changed successfully'
            ]);
        } else {
            return response()->json([
                'error' => 'true',
                'message' => 'Invalid old password'
            ]);
        }
    }

    /**
     * @api {get} /faqs List of FAQs
     * @apiGroup Users
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} faq List of question and answers 
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *  {
     *     "error": "false",
     *     "message": "",
     *     "data": [
     *         {
     *             "id": 1,
     *             "question": "What is Lorem Ipsum?",
     *             "answer": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book."
     *         },
     *         {
     *             "id": 2,
     *             "question": "Why do we use it?",
     *             "answer": "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout."
     *         }
     *     ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getFaqs()
    {
        $faq = Faqs::select('id', 'question', 'answer')->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $faq]);
    }

    /**
     * @api {get} /user/unread_counts Get unread counts of notif and chats
     * @apiGroup Users
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} Data List of count of unread notifications & chats
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *  {
     *     "error": "false",
     *     "message": "",
     *     "data": [
     *         {
     *              "notif_count" : 2,
     *              "chat_count" : 5,
     *         }
     *     ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getUnreadCounts()
    {
        $userId = \Auth::user()->id;

        $chats = \App\Chat::withCount(['messages' => function ($query) use ($userId) {
                        $query->where('read_flag', 0)->where('user_id', '!=', $userId);
                    }])->where(function ($q) use ($userId) {
                    $q->where('user_one', $userId)->orwhere('user_two', $userId);
                })->get();

        $messagesCount = (int) @$chats->sum('messages_count');

        $notificationsCount = UserNotifications::where('user_id', $userId)->where(function ($query) {
                    $query->whereNull('is_read')->orWhere('is_read', 0);
                })->orderBy('created_at', 'desc')->count();

        $unreadCounts = ['chat_count' => $messagesCount, 'notif_count' => $notificationsCount];

        return response()->json(['error' => 'false', 'message' => '', 'data' => $unreadCounts]);
    }


    // get avg speed, punches & force
    private function getAvgSpeedAndForce($userId)
    {
        $session = Sessions::select('id', 'start_time', 'end_time')
                        ->where('user_id', $userId)
                        ->where(function($query) {
                            $query->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->get()->toArray();
        $sessionIds = array_column($session, 'id');

        $totalTime = 0;
        $startDate = [];
        foreach ($session as $time) {
            if ($time['start_time'] > 0 && $time['end_time'] > 0 && $time['end_time'] > $time['start_time']) {
                $totalTime = $totalTime + abs($time['end_time'] - $time['start_time']);
                $startDate[] = date('y-m-d', (int) ($time['start_time'] / 1000));
            }
        }

        $getAvgSession = Sessions::select(
                                \DB::raw('AVG(avg_speed) as avg_speeds'), \DB::raw('AVG(avg_force) as avg_forces'), \DB::raw('MAX(punches_count) as avg_punch'))
                        ->where('user_id', $userId)->where(function ($query) {
                    $query->whereNull('battle_id')->orWhere('battle_id', '0');
                })->first();

        $avgCount = 0;
        $getAvgCount = SessionRounds::select(
                                \DB::raw('SUM(ABS(start_time - end_time)) AS `total_time`'), \DB::raw('SUM(punches_count) as punches'))
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
     * @api {get} /user/notifications Get all unread notifications of current user
     * @apiGroup Users
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} user User's unread notifications
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [ {
     *                "percentage": 1,
     *                "hide": true/false
     *              },
     *              {
     *                 "id": 4,
     *                 "user_id": 1,
     *                 "notification_type_id": 1,
     *                 "text": "Tia Maria is now following you",
     *                 "is_read": null,
     *                 "created_at": false,
     *                 "opponent_user": {
     *                     "id": 31,
     *                     "first_name": "Tia",
     *                     "last_name": "Maria",
     *                     "photo_url": "http://image.example.com/u838574839.jpg",
     *                     "user_following": false,
     *                     "user_follower": true,
     *                     "points": 2367
     *                 }
     *             },
     *             {
     *                 "id": 5,
     *                 "user_id": 1,
     *                 "notification_type_id": 2,
     *                 "text": "Tisa Cott has challenged you for battle",
     *                 "is_read": null,
     *                 "created_at": false,
     *                 "opponent_user": {
     *                     "id": 15,
     *                     "first_name": "Tisa",
     *                     "last_name": "Cott",
     *                     "photo_url": null,
     *                     "user_following": false,
     *                     "user_follower": false,
     *                     "points": 433
     *                 },
     *                 "battle_id": 131
     *             },
     *             {
     *                 "id": 6,
     *                 "user_id": 1,
     *                 "notification_type_id": 4,
     *                 "text": "John Smith likes your post",
     *                 "is_read": null,
     *                 "created_at": false,
     *                 "opponent_user": {
     *                     "id": 16,
     *                     "first_name": "John",
     *                     "last_name": "Smith",
     *                     "photo_url": null,
     *                     "user_following": false,
     *                     "user_follower": false,
     *                     "points": 7247
     *                 },
     *                 "post_id": 1
     *             },
     *             {
     *                 "id": 10,
     *                 "user_id": 1,
     *                 "notification_type_id": 5,
     *                 "text": "Weebo Pet has commented on your post",
     *                 "is_read": null,
     *                 "created_at": false,
     *                 "opponent_user": {
     *                     "id": 22,
     *                     "first_name": "Weebo",
     *                     "last_name": "Pet",
     *                     "photo_url": null,
     *                     "user_following": false,
     *                     "user_follower": false,
     *                     "points": 0
     *                 },
     *                 "post_id": 1
     *             },
     *             {
     *                 "id": 1,
     *                 "user_id": 1,
     *                 "notification_type_id": 3,
     *                 "text": "De Soza has finished battle",
     *                 "is_read": null,
     *                 "created_at": 1513240151,
     *                 "opponent_user": {
     *                     "id": 7,
     *                     "first_name": "De",
     *                     "last_name": "Soza",
     *                     "photo_url": "http://image.example.com/u3847748339.jpg",
     *                     "user_following": true,
     *                     "user_follower": true,
     *                     "points": 3270
     *                 },
     *                 "battle_id": 32,
     *                 "battle_finished": false
     *             }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getNotifications(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('start') : 20);

        // Current week's monday(start) to sunday(ends)
        $currentWeekStart = strtotime("last monday midnight");
        $currentWeekEnd = strtotime("next monday midnight", $currentWeekStart)-1;

        // echo date('d-m-Y h:i A', $currentWeekStart);
        // echo "\n".date('d-m-Y h:i A', $currentWeekEnd);

        // Last week's monday(starts) to sunday(ends)
        $lastWeek = strtotime("-1 week +1 day");
        $lastWeekStart = strtotime("last monday midnight", $lastWeek);
        $lastWeekEnd = strtotime("next monday midnight", $lastWeekStart)-1;

        // echo "\n--------------\n".date('d-m-Y h:i A', $lastWeekStart);
        // echo "\n".date('d-m-Y h:i A', $lastWeekEnd);

        $lastWeekBestSession = \App\Sessions::where('user_id', \Auth::id())
                        ->where('start_time', '>', ($lastWeekStart * 1000))
                        ->where('start_time', '<', ($lastWeekEnd * 1000))
                        ->orderBy('avg_force', 'desc')->limit(1)->first();

        $currentWeekBestSession = \App\Sessions::where('user_id', \Auth::id())
                        ->where('start_time', '>', ($currentWeekStart * 1000))
                        ->where('start_time', '<', ($currentWeekEnd * 1000))
                        ->orderBy('avg_force', 'desc')->limit(1)->first();

        $lastWeekMaxAvgForce = $currentWeekMaxAvgForce = 0;

        if ($lastWeekBestSession) {
            $lastWeekMaxAvgForce = $lastWeekBestSession->avg_force;
        }

        if ($currentWeekBestSession) {
            $currentWeekMaxAvgForce = $currentWeekBestSession->avg_force;
        }

        $percentage = @(($currentWeekMaxAvgForce / $lastWeekMaxAvgForce) * 100);

        $_notifications = UserNotifications::with(['opponentUser' => function ($query) {
                        $query->select(['id', 'first_name', 'last_name', 'photo_url', \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points')]);
                    }])->where('user_id', \Auth::user()->id)
                    ->where(function($q) {
                        $q->whereNull('is_read')->orWhere('is_read', 0);
                    })
                    ->where('notification_type_id', '!=', UserNotifications::TOURNAMENT_ACTIVITY_INVITE)
                    ->orderBy('created_at', 'desc')->offset($offset)->limit($limit)->get();

        $_tournamentInviteNotifications = UserNotifications::with(['opponentUser' => function ($query) {
                        $query->select(['id', 'first_name', 'last_name', 'photo_url', \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points')]);
                    }])->where('user_id', \Auth::user()->id)
                    ->where(function($q) {
                        $q->whereNull('is_read')->orWhere('is_read', 0);
                    })
                    ->where('notification_type_id', '=', UserNotifications::TOURNAMENT_ACTIVITY_INVITE)
                    ->orderBy('created_at', 'desc')->offset($offset)->limit($limit)->get();

        $notifications = [];

        // If user is spectator, or didn't do any training, then app has to hide percentage part
        // by having additional boolean flag in reponse
        $hide = (\Auth::user()->is_spectator) ? true : false;
        $notifications[] = ['percentage' => (int) $percentage, 'hide' => (bool) $hide];

        // Tournament invite notifications
        foreach ($_tournamentInviteNotifications as $notification) {
            $temp = $notification->toArray();
            $opponentUserFullName = $notification->opponentUser->first_name . ' ' . $notification->opponentUser->last_name;

            $temp['text'] = str_replace('_USER1_', $opponentUserFullName, $notification->text);
            $temp['event_activity_id'] = $notification->data_id;

            $notifications[] = $temp;
        }

        // Rest of all notifications
        foreach ($_notifications as $notification) {
            $temp = $notification->toArray();
            $opponentUserFullName = $notification->opponentUser->first_name . ' ' . $notification->opponentUser->last_name;

            $temp['text'] = str_replace('_USER1_', $opponentUserFullName, $notification->text);
            switch ($notification->notification_type_id) {
                case UserNotifications::BATTLE_CHALLENGED:
                    $temp['battle_id'] = $notification->data_id;
                    break;

                case UserNotifications::BATTLE_FINISHED:
                    $temp['battle_id'] = $notification->data_id;

                    $battle = \App\Battles::find($notification->data_id);
                    $temp['battle_finished'] = ($battle) ? filter_var((($battle->user_id == \Auth::id()) ? $battle->user_finished : $battle->opponent_finished), FILTER_VALIDATE_BOOLEAN) : null;
                    break;

                case UserNotifications::FEED_POST_LIKE:
                case UserNotifications::FEED_POST_COMMENT:
                    $temp['post_id'] = $notification->data_id;
                    break;
            }

            $notifications[] = $temp;
        }

        return response()->json([
                    'error' => 'false',
                    'message' => '',
                    'data' => $notifications,
        ]);
    }

    /**
     * @api {get} /user/notifications/read/<notification_id> Mark notifications read
     * @apiGroup Users
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Any="#ID = to mark single notification read", "#to-#from = e.g. 2-10 will mark notificaions read having id in range of 2 to 10", "all = will mark all of current user's notification read"} notification_id Notification e.g. 1, 2-10 or all
     * @apiParamExample {json} Input
     *    {
     *      "notification_id": 20
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} user User's unread notifications
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Marked notifications read",
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function readNotifications(Request $request, $notificationId)
    {
        $notificationIds = [];

        if (is_numeric($notificationId)) {
            $_notifications = UserNotifications::where('id', $notificationId)->where('user_id', \Auth::id());
        } elseif (count($exploded = explode('-', $notificationId)) > 1) {
            $notificationIds = range($exploded[0], $exploded[1]);
            $_notifications = UserNotifications::whereIn('id', $notificationIds)->where('user_id', \Auth::id());
        } elseif (strtolower($notificationId) == 'all') {
            $_notifications = UserNotifications::where('user_id', \Auth::id());
        }

        // Gotta make sure that no any tournament activity notifications marked as read
        $_notifications->where('notification_type_id', '!=', UserNotifications::TOURNAMENT_ACTIVITY_INVITE);
        $_notifications->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);

        return response()->json([
            'error' => 'false',
            'message' => 'Marked notifications read'
        ]);
    }
}