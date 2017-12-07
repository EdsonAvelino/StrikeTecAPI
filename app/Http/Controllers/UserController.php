<?php

namespace App\Http\Controllers;

use Validator;
use App\User;
use App\UserConnections;
use App\Faqs;
use App\Leaderboard;
use App\Battles;
use App\Sessions;
use App\SessionRounds;
use App\SessionRoundPunches;
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
     *          "height": null,
     *          "left_hand_sensor": null,
     *          "right_hand_sensor": null,
     *          "left_kick_sensor": null,
     *          "right_kick_sensor": null,
     *          "is_spectator": 0,
     *          "stance": null,
     *          "show_tip": 1,
     *          "skill_level": "PRO",
     *          "photo_url": "http://example.com/profile/pic.jpg",
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
     *          }
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
                    'is_spectator' => 0
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

        $user = User::with(['preferences', 'country', 'state', 'city'])->find(\Auth::id());

        return response()->json(['error' => 'false', 'message' => 'Registration successful', 'token' => $token, 'user' => \Auth::user()]);
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
     *          "height": null,
     *          "left_hand_sensor": null,
     *          "right_hand_sensor": null,
     *          "left_kick_sensor": null,
     *          "right_kick_sensor": null,
     *          "is_spectator": 0,
     *          "stance": null,
     *          "show_tip": 1,
     *          "skill_level": "PRO",
     *          "photo_url": "http://example.com/profile/pic.jpg",
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
     *          }
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
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->get('facebook_id'))
                return response()->json(['error' => 'true', 'message' => 'User already registered']);
        }

        $user = User::create(['facebook_id' => $request->get('facebook_id'),
                    'first_name' => $request->get('first_name'),
                    'last_name' => $request->get('last_name'),
                    'email' => $request->get('email'),
                    'password' => app('hash')->make(strrev($request->get('facebook_id'))),
                    'show_tip' => 1,
                    'is_spectator' => 0
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

        $user = User::with(['preferences', 'country', 'state', 'city'])->find(\Auth::id());

        return response()->json(['error' => 'false', 'message' => 'Facebook registration successful', 'token' => $token, 'user' => \Auth::user()]);
    }

    /**
     * @api {post} /user/register/fan Register a new FAN App user
     * @apiGroup Users
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {String} company_id Company ID of user
     * @apiParam {String} email Email
     * @apiParam {String} password Password
     * @apiParamExample {json} Input
     *    {
     *      "company_id": "1",
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
     *          "height": null,
     *          "left_hand_sensor": null,
     *          "right_hand_sensor": null,
     *          "left_kick_sensor": null,
     *          "right_kick_sensor": null,
     *          "is_spectator": 0,
     *          "company_id": 1, 
     *          "stance": null,
     *          "show_tip": 1,
     *          "skill_level": "PRO",
     *          "photo_url": "http://example.com/profile/pic.jpg",
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
     *          }
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
    public function registerFan(Request $request)
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
                    'company_id' => $request->get('company_id'),
                    'email' => $request->get('email'),
                    'password' => app('hash')->make($request->get('password')),
                    'show_tip' => 1,
                    'is_spectator' => 0
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

        $user = User::with(['preferences', 'country', 'state', 'city'])->find(\Auth::id());

        return response()->json(['error' => 'false', 'message' => 'Registration successful', 'token' => $token, 'user' => \Auth::user()]);
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
     * @apiParam {Number} [height] Height
     * @apiParam {Boolean} [is_spectator] Spectator true / false
     * @apiParam {String} [stance] Stance
     * @apiParam {Boolean} [show_tip] Show tips true / false
     * @apiParam {String} [skill_level] Skill level of user
     * @apiParam {String} [photo_url] User profile photo-url
     * @apiParam {String} [left_hand_sensor] Left Hand Sensor
     * @apiParam {String} [right_hand_sensor] Right Hand Sensor
     * @apiParam {String} [left_kick_sensor] Left Kick Sensor
     * @apiParam {String} [right_kick_sensor] Right Kick Sensor
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
     *      "height": 6,
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
            'gender' => 'in:male,female',
            'birthday' => 'date',
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
            $user->height = $request->get('height') ?? $user->height;

            $user->left_hand_sensor = $request->get('left_hand_sensor') ?? $user->left_hand_sensor;
            $user->right_hand_sensor = $request->get('right_hand_sensor') ?? $user->right_hand_sensor;
            $user->left_kick_sensor = $request->get('left_kick_sensor') ?? $user->left_kick_sensor;
            $user->right_kick_sensor = $request->get('right_kick_sensor') ?? $user->right_kick_sensor;

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
     *              "height": null,
     *              "left_hand_sensor": null,
     *              "right_hand_sensor": null,
     *              "left_kick_sensor": null,
     *              "right_kick_sensor": null,
     *              "is_spectator": 0,
     *              "stance": null,
     *              "show_tip": 1,
     *              "skill_level": null,
     *              "photo_url": "http://example.com/profile/pic.jpg",
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
     *             "user_follower": false
     *             "total_time_trained": 5235
     *             "total_time_trained": 15090,
     *             "total_day_trained": 32,
     *             "avg_speed": 438,
     *             "avg_force": 7992,
     *             "punches_count": 5854,
     *             "avg_count": 6,
     *             "loose_counts": 1,
     *             "win_counts": 2,
     *             "finished_battles": [
     *                 {
     *                     "battle_id": 119,
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
     *                         "photo_url": "http://172.16.11.45/storage/profileImages/sub-1509460359.png",
     *                         "user_following": false,
     *                         "user_follower": false,
     *                         "points": 5854
     *                     }
     *                 },
     *                 {
     *                     "battle_id": 120,
     *                     "winner": {
     *                         "id": 7,
     *                         "first_name": "Qiang",
     *                         "last_name": "Hu",
     *                         "photo_url": "http://172.16.11.45/storage/profileImages/sub-1509460359.png",
     *                         "user_following": false,
     *                         "user_follower": false,
     *                         "points": 5854
     *                     },
     *                     "loser": null
     *                 },
     *                 {
     *                     "battle_id": 32,
     *                     "winner": {
     *                         "id": 7,
     *                         "first_name": "Qiang",
     *                         "last_name": "Hu",
     *                         "photo_url": "http://172.16.11.45/storage/profileImages/sub-1509460359.png",
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
     *             "user_connection": 4,
	 *			 "achievements": [
	 *				{
	 *						"name": "Belt",
	 *						"description": "Single punch over 600lbs",
	 *						"image": "http://striketec.dev/storage/badges/Iron_Fist_1.png",
	 *						"awarded": true,
	 *						"count": 3,
	 *						"shared": false
	 *					},
	 *					{
	 *						"name": "Badge 1",
	 *						"description": "Single punch over 600lbs",
	 *						"image": "",
	 *						"awarded": true,
	 *						"count": 0,
	 *						"shared": false
	 *					},
	 *					{
	 *						"name": "Badge 2",
	 *						"description": "Single punch over 600lbs",
	 *						"image": "",
	 *						"awarded": true,
	 *						"count": 1,
	 *						"shared": false
	 *					}
	 *				]
     *         }
     *     }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function getUser($userId = null)
    {
        if (!$userId) {
            $userId = \Auth::user()->id;
        }

        // user_following = current user is following this user
        $following = UserConnections::where('follow_user_id', $userId)
                        ->where('user_id', \Auth::user()->id)->exists();

        // user_follower = this user if following current user
        $follow = UserConnections::where('user_id', $userId)
                        ->where('follow_user_id', \Auth::user()->id)->exists();

        $user = User::with(['preferences', 'country', 'state', 'city'])->withCount('followers')->withCount('following')->find($userId);

        $user = $user->toArray();
        $user['user_following'] = (bool) $following;
        $user['user_follower'] = (bool) $follow;

        $leaderboard = Leaderboard::where('user_id', $userId)->first();
        $data = $this->getAvgSpeedAndForce($userId);
        $user['total_time_trained'] = floor($data['total_time_trained']);
        $user['total_day_trained'] = floor($data['total_day_trained']);
        $user['avg_speed'] = floor($data['avg_speed']);
        $user['avg_force'] = floor($data['avg_force']);
        $user['punches_count'] = $leaderboard->punches_count;
        $user['avg_count'] = floor($data['avg_punch']);

        $battles = $this->getFinishedBattles($userId);
        $user['loose_counts'] = $battles['lost'];
        $user['win_counts'] = $battles['won'];
        $user['finished_battles'] = $battles['finished'];
        $userFollowing = 'SELECT follow_user_id FROM user_connections WHERE user_id = ?';
        $connections = UserConnections::where('follow_user_id', $userId)
                ->whereRaw("user_id IN ($userFollowing)", [$userId])
                ->count();
        $user['user_connection'] = $connections;
        $badgeImgURL = env('APP_URL').'/storage/badges/';
        $achievementsArr = array();
        $achievementsArr[0] = [ 'name' => 'Belt',
            'description' => 'Single punch over 600lbs',
            'image' => $badgeImgURL.'Champion.png',
            'awarded' => true,
            'count' => 3,
            'shared' => false
        ];
        $achievementsArr[1] = [ 'name' => 'Badge 1',
            'description' => 'Single punch over 600lbs',
            'image' => $badgeImgURL.'Iron_Fist_1.png',
            'awarded' => true,
            'count' => 0,
            'shared' => false
        ];
        $achievementsArr[2] = [ 'name' => 'Badge 2',
            'description' => 'Single punch over 600lbs',
            'image' => $badgeImgURL.'Iron_Fist_2.png',
            'awarded' => true,
            'count' => 1,
            'shared' => false
        ];
        $user['achievements'] = $achievementsArr;
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
     * @apiParamExample {json} Input
     *    {
     *      "public_profile": true,
     *      "show_achivements": false,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "Preferences have been updated successfully",
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
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

        $userPreferences->save();

        return response()->json([
                    'error' => 'false',
                    'message' => 'Preferences have been updated successfully',
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
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "user_following": false,
     *              "user_follower": false,
     *              "points": 130,
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 150,
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 9,
     *              "first_name": "Keily",
     *              "last_name": "Maxi",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 120,
     *              "photo_url": "http://example.com/image.jpg"
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
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "user_following": false,
     *              "user_follower": false,
     *              "points": 130,
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 150,
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 9,
     *              "first_name": "Keily",
     *              "last_name": "Maxi",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 120,
     *              "photo_url": "http://example.com/image.jpg"
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
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 135,
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "user_following": false,
     *              "user_follower": true,
     *              "points": 140,
     *              "photo_url": "http://example.com/image.jpg"
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
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "user_following": true,
     *              "user_follower": false,
     *              "points": 135,
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "user_following": false,
     *              "user_follower": true,
     *              "points": 140,
     *              "photo_url": "http://example.com/image.jpg"
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
                                    \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points'))
                            ->where('id', $follower->follow_user_id)->first();

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
     *              "photo_url": "http://example.com/image.jpg",
     *              "points": 125
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "photo_url": "http://example.com/image.jpg",
     *              "points": 135
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "photo_url": "http://example.com/image.jpg",
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

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $currentUserFollowing = 'SELECT follow_user_id FROM user_connections WHERE user_id = ?';

        $suggested1 = \DB::table('user_connections')->select('user_id')->where('follow_user_id', \Auth::user()->id)->whereRaw("user_id NOT IN ($currentUserFollowing)", [\Auth::user()->id]);

        $suggestedUsersQuery = \DB::table('user_connections')->select('follow_user_id as user_id')

            ->whereRaw("user_id IN ($currentUserFollowing)", [\Auth::user()->id])
            ->where('follow_user_id', '!=', \Auth::user()->id)
            ->whereRaw("follow_user_id NOT IN ($currentUserFollowing)", [\Auth::user()->id])
            ->union($suggested1);


        $suggestedUsers = \DB::table(\DB::raw("({$suggestedUsersQuery->toSql()}) as raw"))
                        ->select('user_id')->mergeBindings($suggestedUsersQuery)
                        ->offset($offset)->limit($limit)->get();

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
     * @apiParamExample {json} Input
     *    {
     *      "user_id": 7,
     *      "start": 20,
     *      "limit": 50
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
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 6,
     *              "first_name": "Elena",
     *              "last_name": "Jaz",
     *              "points": 135,
     *              "user_following": true,
     *              "user_follower": true,
     *              "photo_url": "http://example.com/image.jpg"
     *          },
     *          {
     *              "id": 8,
     *              "first_name": "Carl",
     *              "last_name": "Lobstor",
     *              "points": 140,
     *              "user_following": false,
     *              "user_follower": true,
     *              "photo_url": "http://example.com/image.jpg"
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

        $userFollowing = 'SELECT follow_user_id FROM user_connections WHERE user_id = ?';

        $connections = [];

        $_connections = UserConnections::where('follow_user_id', $userId)
                        ->whereRaw("user_id IN ($userFollowing)", [$userId])
                        ->offset($offset)->limit($limit)->get();

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
                        'message' => 'Inavlid old password'
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

        // TODO get unread notification counts
        $unreadCounts = ['chat_count' => $messagesCount, 'notif_count' => 0];

        return response()->json(['error' => 'false', 'message' => '', 'data' => $unreadCounts]);
    }

    /**
     * @api {get} /users/list Get list of APP user
     * @apiGroup FAN APP Users
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} [start] Start offset
     * @apiParam {Number} [limit] Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} user User's information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": {
     *             {
     *              "id": 1,
     *              "facebook_id": 1234567890,
     *              "first_name": "John",
     *              "last_name": "Smith",
     *              "email": "john@smith.com",
     *              "gender": null,
     *              "birthday": "1975-05-09",
     *              "weight": null,
     *              "height": null,
     *              "left_hand_sensor": null,
     *              "right_hand_sensor": null,
     *              "left_kick_sensor": null,
     *              "right_kick_sensor": null,
     *              "is_spectator": 0,
     *              "stance": null,
     *              "show_tip": 1,
     *              "skill_level": null,
     *              "photo_url": "http://example.com/profile/pic.jpg",
     *              "updated_at": "2016-02-10 15:46:51",
     *              "created_at": "2016-02-10 15:46:51",
     *          },
     *             {
     *              "id": 12,
     *              "facebook_id": 1234567890,
     *              "first_name": "Anchal",
     *              "last_name": "gupta",
     *              "email": "anchal@gupta.com",
     *              "gender": null,
     *              "birthday": "1975-05-09",
     *              "weight": null,
     *              "height": null,
     *              "left_hand_sensor": null,
     *              "right_hand_sensor": null,
     *              "left_kick_sensor": null,
     *              "right_kick_sensor": null,
     *              "is_spectator": 0,
     *              "stance": null,
     *              "show_tip": 1,
     *              "skill_level": null,
     *              "photo_url": "http://example.com/profile/pic.jpg",
     *              "updated_at": "2016-02-10 15:46:51",
     *              "created_at": "2016-02-10 15:46:51",
     *          }
     *          ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function getUsersList(Request $request)
    {
        $userId = \Auth::user()->id;

        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('start') : 20);

        $users = User::where('id', '!=', $userId)->offset($offset)->limit($limit)->get();

        return response()->json([
                    'error' => 'false',
                    'message' => 'Users list information',
                    'data' => $users,
        ]);
    }

    // get avg speed, punches & force
    private function getAvgSpeedAndForce($userId)
    {
        $session = Sessions::select('id', 'start_time', 'end_time')
                        ->where(function($query) use($userId) {
                            $query->where('user_id', $userId)->whereNull('battle_id')->orWhere('battle_id', '0');
                        })->get()->toArray();
        $sessionIds = array_column($session, 'id');
        $totalTime = $forceCount = 0;
        $punch = $speed_sum = $forces_sum = $startDate = [];
        foreach ($session as $time) {
            if ($time['start_time'] > 0 && $time['end_time'] > 0 && $time['end_time'] > $time['start_time']) {
                $totalTime = $totalTime + abs($time['end_time'] - $time['start_time']);
                $startDate[] = date('y-m-d', (int) ($time['start_time'] / 1000));
            }
        }

        $sessionRounds = SessionRounds::with('punches')->select('id')->whereIn('session_id', $sessionIds)->get()->toArray();

        foreach ($sessionRounds as $sessionRound) {
            $punches = $sessionRound['punches'];
            if ($punches) {
                foreach ($punches as $forces) {
                    $force[$forceCount][] = $forces['force'];
                    $speed[$forceCount][] = $forces['speed'];
                }
                $forces_sum[] = array_sum($force[$forceCount]);
                $speed_sum[] = array_sum($speed[$forceCount]);
            }
            $forceCount++;
            $punch[] = count($sessionRound['punches']);
        }

        $data['total_time_trained'] = floor($totalTime / 1000);
        $data['total_day_trained'] = floor(count(array_unique($startDate)));
        $data['avg_punch'] = floor(array_sum($punch) / count($punch));
        $data['avg_speed'] = floor(array_sum($speed_sum) / count($speed_sum));
        $data['avg_force'] = floor(array_sum($forces_sum) / count($forces_sum));

        return $data;
    }

    // Finished battles of user
    private function getFinishedBattles($userId)
    {
        $battle_finished = Battles::select('battles.id as battle_id', 'winner_user_id', 'user_id', 'opponent_user_id', 'user_finished_at', 'opponent_finished_at')
                        ->where(function ($query) use($userId) {
                            $query->where(['user_id' => $userId])->orWhere(['opponent_user_id' => $userId]);
                        })
                        ->where(['opponent_finished' => TRUE])
                        ->where(['user_finished' => TRUE])
                        ->orderBy('battles.updated_at', 'desc')
                        ->offset(0)->limit(20)->get()->toArray();

        $array = array();
        $countArr = $lost = $won = 0;

        foreach ($battle_finished as $data) {
            if (empty($data['winner_user_id'])) {
                $data['winner_user_id'] = (strtotime($data['user_finished_at']) < strtotime($data['opponent_finished_at'])) ? $data['user_id'] : $data['opponent_user_id'];
            }

            $loserId = ($data['winner_user_id'] == $data['user_id']) ? $data['opponent_user_id'] : $data['user_id'];

            $array[$countArr]['battle_id'] = $data['battle_id'];
            $array[$countArr]['winner'] = User::get($data['winner_user_id']);
            $array[$countArr]['loser'] = User::get($loserId);

            if ($data['winner_user_id'] == $userId) {
                $won = $won + 1;
            }

            if ($loserId == $userId) {
                $lost = $lost + 1;
            }

            $countArr++;
        }

        return ['lost' => $lost, 'won' => $won, 'finished' => $array];
    }

}
