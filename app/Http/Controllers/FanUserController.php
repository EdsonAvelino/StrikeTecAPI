<?php

namespace App\Http\Controllers;

use App\AdminUsers;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

use App\Mail\PasswordGenerateCodeEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

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
     * @api {post} /fan/user/register Register new FAN Ambassador
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
     *           "role": "ambassador",
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
    public function registerFanAdmin(Request $request)
    {         
        $validator = \Validator::make($request->all(), [
            'email' => 'required|max:64|unique:admin_users',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors->first('email')]);
        }

        $user = AdminUsers::create([
            'company_id' => $request->get('company_id'),
            'email' => $request->get('email'),
            'password' => app('hash')->make($request->get('password')),
            'is_web_admin' => null,
            'is_fan_app_admin' => null,
        ]);

        try {
            if (!$token = Auth::guard('fan')->attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'true', 'message' => 'Invalid request']);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token has been expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'true', 'message' => 'Invalid token'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token does not exists'], $e->getStatusCode());
        }

        // $user = AdminUsers::select('*', 'role')->with('company')->find(\Auth::id());

        return response()->json(['error' => 'false', 'message' => 'Registration successful', 'token' => $token, 'data' => $user]);
    }
    
    /**
     * @api {post} /fan/auth/login Login FAN admin/ambassador user
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
     *           "role": "admin",
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
        $validator = \Validator::make($request->all(), [
            'email'    => 'required|email|max:255',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors->first('email')]);
        }

        try {
            if (! $token = Auth::guard('fan')->attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'true', 'message' => 'Invalid credentials or user is not registered'], 200);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token has been expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'true', 'message' => 'Invalid token'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token does not exists'], $e->getStatusCode());
        }

        $fanUserId = \Auth::guard('fan')->id();
        $user = AdminUsers::select('*', \DB::Raw('id as role'))->with('company')->find($fanUserId);

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
    * @apiSuccess {String} message Error / Success message
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

    /**
     * @api {get} /fan/users Get list of users (Users Database)
     * @apiGroup Events
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParam {String} [query] Search users by name or email
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
     *               "email": "jackx@example.com",
     *           },
     *           {
     *               "id": 2,
     *               "first_name": "Mel",
     *               "last_name": "Sultana",
     *               "photo_url": "http://example.com/users/user_pic-1513164799.jpg",
     *               "email": "mels@example.com",
     *           },
     *           {
     *               "id": 3,
     *               "first_name": "Karl",
     *               "last_name": "Lobster",
     *               "photo_url": "http://example.com/users/user_pic-1513164799.jpg",
     *               "email": "karls@example.com",
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
    public function getUsersList(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        
        $query = trim($request->get('query') ?? null);

        $companyId = \Auth::user()->company_id;
        
        $_users = \App\User::select('id', 'first_name', 'last_name', 'email', 'photo_url');

        if ($query) {
            $_users->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%$query%")
                    ->orWhere('last_name', 'LIKE', "%$query%")
                    ->orWhere('email', 'LIKE', "%$query%");
            });
        }
        
        $users = $_users->offset($offset)->limit($limit)->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $users]);
    }

    /**
     * @api {post} /fan/users Add new user to DB
     * @apiGroup Events
     * @apiHeader {String} Content-Type application/form-data
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/form-data"
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} name Name of user
     * @apiParam {String} email Email
     * @apiParam {String="male","female"} [gender] Gender
     * @apiParam {Date} [dob] Birthday in MM/DD/YYYY e.g. 09/11/1987
     * @apiParam {Number} [weight] Weight
     * @apiParam {Number} [height] Height
     * @apiParam {string} [profile_image] Image of user profile 
     * @apiParamExample {json} Input
     *    {
     *      "name": "John",
     *      "email": "john@smith.com",
     *      "dob":09/11/1987
     *      "weight":65
     *      "height":160
     *      "gender":male
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Contains new created user-id 
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "user has been added to DB",
     *      "data": {
     *          "user_id": 54
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
    public function postUserToDb(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'required|max:64|unique:users',
            'dob' => 'date',
            'gender' => 'in:male,female',
            'profile_image' => 'mimes:jpeg,jpg,png'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' => $errors]);
        }
        
        $imageStoragePath = config('striketec.storage.users');

        // Creates a new user
        if ($request->hasFile('profile_image')) {
            $userProfileImage = $request->file('profile_image');
            $userProfileImageOrigName = $userProfileImage->getClientOriginalName();
            
            $profilePicName = pathinfo($userProfileImageOrigName, PATHINFO_FILENAME);
            $profilePicExt = pathinfo($userProfileImageOrigName, PATHINFO_EXTENSION);
            
            $profilePicName = preg_replace("/[^a-zA-Z]/", "_", $profilePicName);

            $userProfileImageName = 'u_'. md5($profilePicName) . '_' . time() . '.' . $profilePicExt;
            $userProfileImage->move($imageStoragePath, $userProfileImageName);
            
            $userProfileImage = $userProfileImageName;
        }

        $passwordStr = '!@$&abcdefghjkmnprstuwxyzABCDEFGHJKLMNPQRSTUWXYZ23456789';
        $password = substr(str_shuffle($passwordStr), 0, 9);

        $user = \App\User::create([
                    'first_name' => $request->get('name'),
                    'password' => app('hash')->make($password),
                    'email' => $request->get('email'),
                    'gender' => $request->get('gender'),
                    'weight' => $request->get('weight'),
                    'height' => $request->get('height'),
                    'birthday' => date('Y-m-d', strtotime($request->get('dob'))),
                    'photo_url' => (isset($userProfileImage) && !empty($userProfileImage)) ? $userProfileImage : NULL
                ]);

        $subject = 'Welcome to StrikeTec';
        
        // Mail to user
        Mail::to($request->get('email'))->send(new PasswordGenerateCodeEmail($subject, $user, $password));

        return response()->json(['error' => 'false', 'message' => 'User has been added to DB', 'data' => ['user_id' => $user->id]]);
    }
}