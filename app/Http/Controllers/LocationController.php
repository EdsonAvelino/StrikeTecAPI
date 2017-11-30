<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Location;

Class Locationcontroller extends Controller
{
    /**
     * @api {post} /addLocation register new location  
     * @apiGroup User Subscription
     * @apiHeader {String} authorization Authorization value
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} Data list of user subscription status
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *   "error": "false",
     *   "message": "",
     *   "data": 1
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function addLocation(Request $request)
    {
        $location = Location::create([
            'name' => $request->name
        ])->id;
        return response()->json(['error' => 'false', 'message' => '', 'data' => $location]);
    }
    
    /**
     * @api {get} /getLocationList get location list
     * @apiGroup Location
     * @apiHeader {String} authorization Authorization value
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} Data list of location name
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *   "code": "200",
     *   "message": "success",
     *   "data": {
     *   "subscription": {
     *   "id": 1,
     *   "SKU": "plan1",
     *   "tutorials": "5",
     *   "tournaments": "2",
     *   "battles": "2",
     *   "tournament_details": "Additional for $1.99",
     *   "battle_details": "Additional for $1.99",
     *   "tutorial_details": "Additional for $1.99",
     *   "name": "Free Plan",
     *   "duration": "until exhausted",
     *   "price": 0,
     *   "created_at": "-0001-11-30 00:00:00",
     *   "modified_at": "0000-00-00 00:00:00"
     *   },
     *   "user_subscription": {
     *   "battles": "You had 4 Battles remaining untill Dec 15th,2017",
     *   "tutorials": "You had 10 Tutorials remaining untill Dec 15th,2017",
     *   "tournaments": "You had 4 Tournaments remaining untill Dec 15th,2017",
     *   "purchase_token": "token_1234",
     *   "expiry_date": "2017-12-15",
     *   "is_cancelled": true
     *   }
     *   }
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getLocationList() 
    {   
        $location_list = array();
        $location_list = Location::all();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $location_list]);
    }
}

