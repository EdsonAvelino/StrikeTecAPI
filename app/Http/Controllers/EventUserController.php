<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\EventUser;
use App\User;
use App\Mail\PasswordGenerateCodeEmail;
use Illuminate\Support\Facades\Mail;

Class EventUserController extends Controller
{

    /**
     * @api {post} /fan/event/users/add register user to event
     * @apiGroup event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id id of event
     * @apiParam {int} user_id list of user ID
     * @apiParamExample {json} Input
     *    {
     *      "event_id": "2",
     *      "user_id": "1,2,3",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccess {Object} data Event create successfully
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *       "error": "false",
     *       "message": "User has been added successfully",
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function usersAddEvent(Request $request)
    {        
        $data = $request->input();
        $user_ids = explode(',', $data['user_id']);
        /*
         ***** array_values for start indexing again with 0 ******
         ***** array_filter for filtring null value *******
         *****   array uniqe for store only uniqe value  *****
        */
        $user_id = array_values(array_filter(array_unique($user_ids)));
        for($count = 0; $count < count($user_id); $count++) {
            $checkUserIdExist = EventUser::where(['user_id' => $user_id[$count], 'event_id' => $data['event_id'], 'status' => 0])
                                        ->first();
            if($checkUserIdExist) {
                $updateStatusExistUserID = EventUser::where(['user_id' => $user_id[$count], 'event_id' => $data['event_id']])->update(['status' => 1]);
            } else {
                $checkUserIdExist = EventUser::where(['user_id' => $user_id[$count], 'event_id' => $data['event_id'], 'status' => 1])
                                            ->first();
                if(!$checkUserIdExist) {
                    $userIdStore = EventUser::create([
                        'user_id' => $user_id[$count],
                        'event_id' => $data['event_id'],
                        'status' => 1
                    ]);
                }
            }
        }
        return response()->json(['error' => 'false', 'message' => 'User has been added successfully']);
    }

    /**
     * @api {post} /fan/event/users/remove remove users from event
     * @apiGroup event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {int} event_id id of event
     * @apiParam {int} user_id id of user
     * @apiParamExample {json} Input
     *    {
     *      "event_id": 1,
     *      "user_id": 1,2,3
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message / Success message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *       "error": "false",
     *       "message": "Users has been removed from event successfully",
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
    */
    public function eventUsersRemove(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'event_id'    => 'required|exists:event_users',
            'user_id' => 'required'
        ]);
        if ($validator->fails()) { 
            $errors = $validator->errors();
            return response()->json(['error' => 'true', 'message' =>  $errors]);
        }
        try {
            $data = $request->input();
            $user_ids = explode(',', $data['user_id']);
            /*
             ***** array_values for start indexing again with 0 ******
             ***** array_filter for filtring null value *******
             ***** array uniqe for store only uniqe value  *****
            */
            $user_id = array_values(array_filter(array_unique($user_ids)));
            for($count = 0; $count < count($user_id); $count++) {
                $event_id = $request->get('event_id');
                EventUser::where('event_id', $event_id)
                            ->where('user_id', $user_id[$count])->delete();
            }
            return response()->json([
                'error' => 'false',
                'message' => 'Users has been removed from event successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                    'error' => 'true',
                    'message' => 'Invalid request',
            ]);
        }
    }
    
    /**
     * @api {post} /fan/event/register/user Add new user to Event
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/form-data
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/form-data"
     *     }
     * @apiParam {String} name Name of user
     * @apiParam {String} event_id event id
     * @apiParam {String} email Email
     * @apiParam {String="male","female"} [gender] Gender
     * @apiParam {Date} [dob] Birthday in MM-DD-YYYY e.g. 09/11/1987
     * @apiParam {Number} [weight] Weight
     * @apiParam {Number} [height] Height
     * @apiParam {string} [profile_image] image of user profile 
     * @apiParamExample {json} Input
     *    {
     *      "name": "John",
     *      "email": "john@smith.com",
     *      "event_id":5
     *      "dob":09/11/1987
     *      "weight":65
     *      "height":160
     *      "gender":male
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data user id and event id
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *     "message": "user has been added and Password sent on the email id.",
     *      "data": {
     *          "event_id": "5",
     *          "user_id": 54
     *          }
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function addUserToDb(Request $request)
    {
        $userProfile = '';
        $name = $request->input('name');
        $email = $request->input('email');
        $eventId = !empty($request->input('event_id')) ? $request->input('event_id') : '';
        $gender = $request->input('gender');
        $dob = $request->input('dob');
        $weight = $request->input('weight');
        $height = $request->input('height');
        try {
            $validator = Validator::make($request->all(), [
                        'email' => 'required|max:64',
                        'gender' => 'in:male,female', 
                        'dob' => 'date',
                        'profile_image' => 'mimes:jpeg,jpg,png'
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json(['error' => 'true', 'message' => $errors]);
            }
            $user = User::select('id')->where('email', $email)->first();
            if ($user) { // Creates a new user
                $userId = $user->id;
            } else {
                /* user profile pic */
                if ($request->hasFile('profile_image')) {
                    $userProfileInput = $request->file('profile_image');
                    $imagePath = 'storage/fanuser/profilepic';
                    $userProfileInformation = $userProfileInput->getClientOriginalName();
                    $profilePicName = pathinfo($userProfileInformation, PATHINFO_FILENAME);
                    $profilePicEXT = pathinfo($userProfileInformation, PATHINFO_EXTENSION);
                    $userProfileInformation = $profilePicName . '-' . time() . '.' . $profilePicEXT; 
                    $userProfileInput->move($imagePath, $userProfileInformation);
                    $userProfile = url() . '/' . $imagePath . '/' . $userProfileInformation; // path to be inserted in table
                }
                $userId = $this->createUser($name, $email, $gender, $weight, $height, $dob, $userProfile);
            }
            //here we put code for user create with out event id
            if($eventId == 0) {
                if($user) {
                    return response()->json(['error' => 'true', 'message' => 'User email id already registered.']);
                }
                return response()->json([ 'error' => 'false', 'message' => 'User has been registerd successfully.', 'data' => ['user_id' => $userId]]);
            }
            // here if event id is there 
            $checkUser = EventUser::where(function ($query) use ($userId, $eventId) {
                        $query->where('user_id', $userId)->Where('event_id', $eventId);
                    })->exists();
            if ($checkUser == true) {
                return response()->json(['error' => 'true', 'message' => 'user already registered to the event.']);
            }
            EventUser::create(['user_id' => $userId, 'event_id' => $eventId, 'status' => 1]);

            return response()->json([ 'error' => 'false', 'message' => 'User has been added.', 'data' => ['event_id' => $eventId, 'user_id' => $userId]]);
        } catch (\Exception $e) {

            return response()->json(['error' => 'true', 'message' => $e->getMessage()]);
        }
    }

// Generates a strong password of N length containing at least one lower case letter,
// one uppercase letter, one digit, and one special character. The remaining characters
    public function generateStrongPassword($length = 9, $available_sets = 'luds')
    {
        $sets = array();
        if (strpos($available_sets, 'l') !== false) {
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        }
        if (strpos($available_sets, 'u') !== false) {
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        }
        if (strpos($available_sets, 'd') !== false) {
            $sets[] = '23456789';
        }
        if (strpos($available_sets, 's') !== false) {
            $sets[] = '!@#$%&*?';
        }
        $all = $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for ($count = 0; $count < $length - count($sets); $count++)
            $password .= $all[array_rand($all)];
        $password = str_shuffle($password);

        return $password;
    }

//create user if not exist
    public function createUser($name, $email, $gender, $weight, $height, $dob, $userProfile)
    {
        $pass = $this->generateStrongPassword();
        $userId = User::create([
                    'first_name' => $name,
                    'password' => app('hash')->make($pass),
                    'email' => $email,
                    'gender' => $gender,
                    'weight' => $weight,
                    'height' => $height,
                    'birthday' => date('Y-m-d', strtotime($dob)),
                    'photo_url' => !empty($userProfile) ? $userProfile : NULL
                ])->id;
        $subject = 'StrikeTec: User password';
        Mail::to($email)->send(new PasswordGenerateCodeEmail($subject, $pass, $name));
        return $userId;
    }

}
