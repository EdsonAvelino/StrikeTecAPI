<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Location;

Class Locationcontroller extends Controller
{
   
    public function addLocation(Request $request)
    {
        $location = Location::create([
            'name' => $request->name
        ])->id;

        return response()->json(['error' => 'false', 'message' => '', 'data' => $location]);
    }
    
    /**
     * @api {get} /fan/locations Get list of locations
     * @apiGroup Events
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Contains list of locations
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *   "code": "200",
     *   "message": "success",
     *   "data":  [
     *           {
     *               "id": 1,
     *               "name": "Las Vegas, Nevada",
     *           },
     *           {
     *               "id": 2,
     *               "name": "Manhattan, New York",
     *           },
     *           {
     *               "id": 2,
     *               "name": "San Francisco",
     *           }
     *       ]
     *     }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getLocationsList() 
    {   
        $locations = Location::all();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $locations]);
    }
}

