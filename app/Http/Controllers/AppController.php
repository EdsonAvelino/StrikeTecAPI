<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppController extends Controller
{
	/**
     * @api {post} /check_update Check for app update
     * @apiGroup App
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiParam {Number} version Current application version
     * @apiParam {String="Android", "IOS"} os OS Platform
     * @apiParamExample {json} Input
     *    {
     *      "version": 1.0.5,
     *      "os": "Android"
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Boolean} update Update available or not
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "update": true,
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */

    public function checkForUpdate(Request $request)
    {
    	$version = $request->get('version');
	    $os = strtolower($request->get('os'));

	    $appVersion = \DB::table('app_version')->first();

	    if ($appVersion->{$os.'_v'} > $version) {
	        return response()->json(['error' => 'false', 'message' => '', 'update' => true]);
	    }

	    return response()->json(['error' => 'false', 'message' => '', 'update' => false]);
    }
}