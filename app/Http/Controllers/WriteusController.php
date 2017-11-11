<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\WriteUs;
use App\Mail\WriteUsEmail;

class WriteusController extends Controller
{

    /**
     * @api {post} /writeus Write Us email
     * @apiGroup Write Us
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *     }
     * @apiParam {String} email Email
     * @apiParam {String} subject Subject
     * @apiParam {String} message Message
     * @apiParamExample {json} Input
     *    {
     *      "email"  : "john@smith.com",
     *      "subject": "something",
     *      "message": "Lorem Ipsum is simply dummy text of the printing"
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Thank you for contacting us.",
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function writeUs(Request $request)
    {
        $validator = Validator::make($request->all(), [
                    'email' => 'required|email|max:255',
                    'subject' => 'required|min:2',
                    'message' => 'required|min:2',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->get('email'))
                return response()->json(['error' => 'true', 'message' => $errors->first('email')]);
            else if ($errors->get('subject'))
                return response()->json(['error' => 'true', 'message' => $errors->first('subject')]);
            else if ($errors->get('message'))
                return response()->json(['error' => 'true', 'message' => $errors->first('message')]);
        }

        WriteUs::create([
            'email' => ( $email = $request->get('email') ),
            'message' => ( $message = $request->get('message') ),
            'subject' => ( $subject = $request->get('subject') )
        ]);

        // Admin Email
        $adminEmail = 'team@striketec.com';

        Mail::to($adminEmail)->send(new WriteUsEmail($email, $subject, $message));

        return response()->json(['error' => 'false', 'message' => 'Thank you for contacting us.']);
    }

}
