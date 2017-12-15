<?php

namespace App\Http\Controllers;

use Validator;
use App\FanUser;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class FanUserController extends Controller
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
     * @api {post} /fan/user/register/ Register a new FAN App user
     * @apiGroup Fan User
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {String} company_id Company ID of user
     * @apiParam {String} [name] Name of user
     * @apiParam {String} email Email
     * @apiParam {String} password Password
     * @apiParamExample {json} Input
     *    {
     *      "company_id": "3",
     *      "name": "Jhon"
     *      "email": "john@smith.com",
     *      "password": "Something123"
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {String} token Access token
     * @apiSuccess {Object} user User object contains user's all information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   "error": "false",
     *   "message": "Authentication successful",
     *   "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJOi8vMTcyLjE2LjEwLj...joiV2RMUjlCOFZXZHB2UFdIeiIsInN1YiI6MX0.K38EibXxEvFwv4WpTc8zkQDNE",
     *       "data": {
     *           "id": 1,
     *           "company_id": 3,
     *           "name": "Jhon",
     *           "email": "john@smith.com",
     *           "created_at": "2017-12-06 11:21:01",
     *           "updated_at": "2017-12-06 17:10:56",
     *           "company": {
     *               "id": 3,
     *               "company_name": "Direct Tv",
     *               "created_at": "2017-11-14 22:03:34",
     *               "updated_at": "2017-11-14 22:03:34"
     *           }
     *       }
     *   }
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
                    'email' => 'required|max:64|unique:fan_users',
                 // 'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*+_-])(?=.*\d)[A-Za-z0-9~!@#$%^&*+_-]{8,}$/',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors->first('email')]);
        }
        $user = FanUser::create([
                    'company_id' => $request->get('company_id'),
                    'name'  => $request->get('name'),
                    'email' => $request->get('email'),
                    'password' => app('hash')->make($request->get('password')),
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

        $user = FanUser::with('company')->find(\Auth::id());

        return response()->json(['error' => 'false', 'message' => 'Registration successful', 'token' => $token, 'data' => $user]);
    }
    
    /**
     * @api {post} /fan/login/auth Login For fan user
     * @apiGroup Fan User
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {String} email Email
     * @apiParam {String} password Password
     * @apiParamExample {json} Input
     *    {
     *      "company_id": "3",
     *      "name": "Jhon"
     *      "email": "john@smith.com",
     *      "password": "Something123"
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {String} token Access token
     * @apiSuccess {Object} user User object contains user's all information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   "error": "false",
     *   "message": "Authentication successful",
     *   "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJOi8vMTcyLjE2LjEwLj...joiV2RMUjlCOFZXZHB2UFdIeiIsInN1YiI6MX0.K38EibXxEvFwv4WpTc8zkQDNE",
     *       "data": {
     *           "id": 1,
     *           "company_id": 3,
     *           "name": "Jhon",
     *           "email": "john@smith.com",
     *           "created_at": "2017-12-06 11:21:01",
     *           "updated_at": "2017-12-06 17:10:56",
     *           "company": {
     *               "id": 3,
     *               "company_name": "Direct Tv",
     *               "created_at": "2017-11-14 22:03:34",
     *               "updated_at": "2017-11-14 22:03:34"
     *           }
     *       }
     *   }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
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
        $user = FanUser::with('company')->find(\Auth::id());
        return response()->json(['error' => 'false', 'message' => 'Authentication successful', 'token' => $token, 'data' => $user]);
    }
    
    
     /**
     * @api {post} /fan/user/change_password Change Fan user's password
     * @apiGroup Fan User
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
    public function setFanUserPassword(Request $request)
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
}
