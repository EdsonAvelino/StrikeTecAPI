<?php

namespace App\Http\Controllers;

use Validator;
use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class AuthController extends Controller
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
     * @api {post} /auth/login Login with creds
     * @apiGroup Authentication
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {String} email Email
     * @apiParam {String} password Password
     * @apiParamExample {json} Input
     *    {
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
                "id": 1,
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
     *          },
     *          "points": 2500
     *      }
     *    }
     * @apiErrorExample {json} Login error (Invalid credentials)
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid credentials or user is not registered"
     *      }
     * @apiVersion 1.0.0
     */

    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|max:255',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('email')]);
        }

        try {
            if (! $token = $this->jwt->attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'true', 'message' => 'Invalid credentials or user is not registered'], 200);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token has been expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'true', 'message' => 'Invalid token'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token does not exists'], $e->getStatusCode());
        }

        $user = User::with(['preferences', 'country', 'state', 'city', 'company'])->find(\Auth::id())->toArray();

        $userPoints = User::select('id as points')->where('id', $user['id'])->pluck('points')->first();
        $user['points'] = (int) $userPoints;
        
        // Subscription details (which iap product user has subscribed)
        $user['subscription'] = User::getSubscription($user['id']);

        // Subscription check flag for app to check user's subscription status on google/appstore 
        $subscriptionCheck = User::select('id as subscription_check')->where('id', $user['id'])->pluck('subscription_check')->first();
        $user['subscription_check'] = (bool) $subscriptionCheck;

        return response()->json(['error' => 'false', 'message' => 'Authentication successful', 'token' => $token, 'user' => $user]);
    }

    /**
     * @api {post} /auth/facebook Login with Facebook
     * @apiGroup Facebook Auth
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {String} facebook_id Facebook ID from facebook response
     * @apiParamExample {json} Input
     *    {
     *      "facebook_id": "1234567890",
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
                "id": 1,
     *          "facebook_id": 1234567890,
     *          "first_name": "John",
     *          "last_name": "Smith",
     *          "email": "john@smith.com",
     *          "gender": null,
     *          "birthday": "1970-01-01",
     *          "weight": null,
     *          "height": null,
     *          "left_hand_sensor": null,
     *          "right_hand_sensor": null,
     *          "left_kick_sensor": null,
     *          "right_kick_sensor": null,
     *          "is_spectator": 0,
     *          "stance": null,
     *          "show_tip": 1,
     *          "skill_level": null,
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
     *          },
     *          "points": 2752
     *      }
     *    }
     * @apiErrorExample {json} Authentication error
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid credentials or user not found"
     *      }
     * @apiVersion 1.0.0
     */
    public function authenticateFacebook(Request $request)
    {
        $user = User::where('facebook_id', $request->get('facebook_id'))->first();

        if (!$user) {
            return response()->json(['error' => 'true', 'message' => 'Invalid request or user not found']);
        }

        try {
            if (! $token = $this->jwt->attempt(['email' => $user->email,
                    'password' => strrev($request->get('facebook_id'))]))
            {
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
        
        // Subscription details (which iap product user has subscribed)
        $user['subscription'] = User::getSubscription($user['id']);

        // Subscription check flag for app to check user's subscription status on google/appstore 
        $subscriptionCheck = User::select('id as subscription_check')->where('id', $user['id'])->pluck('subscription_check')->first();
        $user['subscription_check'] = (bool) $subscriptionCheck;

        return response()->json(['error' => 'false', 'message' => 'Authentication successful', 'token' => $token, 'user' => $user]);
    }
}