<?php

namespace App\Http\Controllers;

use App\Subscriptions;

class SubscriptionController extends Controller
{

    /**
     * @api {get} /subscriptions Get Subscription data
     * @apiGroup Subscription
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of Subscription
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *  {
     *    "error": "false",
     *    "message": "",
     *    "data": [
     *        {
     *            "id": 1,
     *            "tutorials": "5",
     *            "tutorial_details": "Additional for $1.99",
     *            "tournaments": "2",
     *            "tournament_details": "Additional for $1.99",
     *            "battles": "2",
     *            "battle_details": "Additional for $1.99",
     *            "name": "Free Plan",
     *            "duration": "until exhausted",
     *            "price": 0,
     *            "SKU": "plan1"
     *        },
     *        {
     *            "id": 2,
     *            "tutorials": "5",
     *            "tutorial_details": "Additional for $1.99",
     *            "tournaments": "2",
     *            "tournament_details": "Additional for $1.99",
     *            "battles": "2",
     *            "battle_details": "Additional for $1.99",
     *            "name": "Monthly Plan",
     *            "duration": "per month",
     *            "price": 3.99,
     *            "SKU": "plan2"
     *        },
     *        {
     *            "id": 3,
     *            "tutorials": "all",
     *            "tutorial_details": "Additional for $1.99",
     *            "tournaments": "all",
     *            "tournament_details": "Additional for $1.99",
     *            "battles": "all",
     *            "battle_details": "Additional for $1.99",
     *            "name": "Annually Plan ",
     *            "duration": "per month",
     *            "price": 9.99,
     *            "SKU": "plan3"
     *        }
     *    ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getSubscriptionList()
    {
        $SubscriptionsList = Subscriptions::select('id', 'tutorials', 'tutorial_details', 'tournaments', 'tournament_details', 'battles', 'battle_details', 'name', 'duration', 'price', 'SKU')
                ->get();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $SubscriptionsList]);
    }

}
