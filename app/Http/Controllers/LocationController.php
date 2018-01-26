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
     * @api {get} /fan/locations get location list
     * @apiGroup Event
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} Data list of location name
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *   "code": "200",
     *   "message": "success",
     *   "data":  [
     *           {
     *               "id": 1,
     *               "name": "noida",
     *           },
     *           {
     *               "id": 2,
     *               "name": "delhi",
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

