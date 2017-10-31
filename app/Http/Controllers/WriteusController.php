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
     *          "message": "Invalid request, user not found"
     *      }
     * @apiVersion 1.0.0
     */
    public function writeUs(Request $request)
    {
        $validator = Validator::make($request->all(), [
                    'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors->first('email')]);
        }
        WriteUs::create([
            'email' => $request->email,
            'message' => $request->message,
            'subject' => $request->subject
        ]);

        $to_email = env('MAIL_USERNAME');
        Mail::to($to_email)->send(new WriteUsEmail($request->email, $request->subject, $request->message));

        return response()->json(['error' => 'false', 'message' => 'Thank you for contacting us.']);
    }

}
