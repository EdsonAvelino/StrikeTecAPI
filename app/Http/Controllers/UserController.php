<?php

namespace App\Http\Controllers;

use App\User;
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
     * @apiParam {String} email Email
     * @apiParam {String} password Password
     * @apiParamExample {json} Input
     *    {
     *      "email": "john@smith.com",
     *      "password": "Something123"
     *    }
     * @apiSuccess {Bookean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {String} token Access token
     * @apiSuccess {Object} user User object contains all user's information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Authentication successful",
     *      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM",
     *      "user": {
                "id": 1,
     *          "facebook_id": null,
     *          "first_name": "Nawaz",
     *          "last_name": "Me",
     *          "email": "ntestinfo@gmail.com",
     *          "gender": null,
     *          "birthday": "1970-01-01",
     *          "weight": null,
     *          "height": null,
     *          "left_hand_sensor": null,
     *          "right_hand_sensor": null,
     *          "left_kick_sensor": null,
     *          "right_kick_sensor": null,
     *          "is_spectator": null,
     *          "stance": null,
     *          "updated_at": "2016-02-10T15:46:51.778Z",
     *          "created_at": "2016-02-10T15:46:51.778Z"
     *      }
     *    }
     * @apiErrorExample {json} Login error (Invalid credentials)
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|max:64|unique:users',
            'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[ A-Za-z0-9_@.#&+-]{6,}$/',
        ]);

        // Creates a new user
        $user = User::create([
            'email' => $request->get('email'),
            'password' => app('hash')->make($request->get('password'))
        ]);
        
        try {
            if (! $token = $this->jwt->attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'ture', 'message' => 'Invalid request']);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token has been expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'true', 'message' => 'Invalid token'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['error' => 'true', 'message' => 'Token does not exists'], $e->getStatusCode());
        }

        return response()->json(['error' => 'false', 'message' => 'Registration successful', 'token' => $token, 'user' => \Auth::user()]);
    }

    /**
     * @api {post} /users Update a user
     * @apiGroup Users
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} first_name First Name
     * @apiParam {String} last_name Last Name
     * @apiParam {String="male","female"} gender Gender
     * @apiParam {Date} birthday Birthday in MM-DD-YYYY e.g. 09/11/1987
     * @apiParam {Number} weight Weight
     * @apiParam {Number} height Height
     * @apiParam {String} stance Stance
     * @apiParamExample {json} Input
     *    {
     *      "first_name": "John",
     *      "last_name": "Smith",
     *      "gender": "male",
     *      "birthday": "09/11/1987",
     *      "weight": 25,
     *      "height": 6,
     *      "stance": "traditional",
     *    }
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "User details have been updated successfully"
     *      }
     * @apiErrorExample {json} Invalid request
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'gender' => 'in:male,female',
            'birthday' => 'date',
        ]);

        try {
            $user = \Auth::user();
            $user->first_name = $request->get('first_name');
            $user->last_name = $request->get('last_name');
            $user->gender = $request->get('gender');
            $user->birthday = date('Y-m-d', strtotime($request->get('birthday')));
            $user->weight = $request->get('weight');
            $user->height = $request->get('height');
            $user->stance = $request->get('stance');

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
}