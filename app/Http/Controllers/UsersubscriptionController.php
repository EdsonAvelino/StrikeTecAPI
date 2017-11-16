<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserSubscriptions;
use App\Subscriptions;

class UsersubscriptionController extends Controller
{

    /**
     * @api {post} /usersubscription register user subscription details
     * @apiGroup In-app Subscription
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} subscription_id subscription id
     * @apiParam {String} purchase_token Purchase Token
     * @apiParam {String} order_id Order Id
     * @apiParam {String} purchase_time Purchased Time
     * @apiParam {Boolean} is_auto_renewing it could be 0 or 1
     * @apiParamExample {json} Input
     *    {
     *      "subscription_id": "1",
     *      "purchase_token": "token_123456789",
     *      "order_id": "141002221001445586",
     *      "purchase_time": "2017-10-24 16:59:28",
     *      "is_auto_renewing": "1",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of Subscription
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   "code": "200",
     *   "message": "success",
     *   "data": {
     *      "subscription": {
     *            "id": 2,
     *             "SKU": "plan2",
     *             "tutorials": "5",
     *             "tournaments": "2",
     *             "battles": "2",
     *             "tournament_details": "Additional for $1.99",
     *             "battle_details": "Additional for $1.99",
     *             "tutorial_details": "Additional for $1.99",
     *             "name": "Monthly Plan",
     *             "duration": "per month",
     *             "price": 3.99,
     *             "created_at": "-0001-11-30 00:00:00",
     *             "modified_at": "0000-00-00 00:00:00"
     *            },
     *      "user_subscription": {
     *          "user_subscription_id": 55,
     *          "battles": "You have 2 Battles remaining untill Dec 16th,2017",
     *          "tutorials": "You have 5 Tutorials remaining untill Dec 16th,2017",
     *          "tournaments": "You have 2 Tournaments remaining untill Dec 16th,2017",
     *          "purchase_token": "token_1234",
     *          "expiry_date": "2017-12-16",
     *          "is_cancelled": false
     *             }
     *       }
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function userSubscribe(Request $request)
    {
        $user_id = $request->user_id;
        $subscription_id = $request->subscription_id;
        $order_id = $request->order_id;
        $purchase_token = $request->purchase_token;
        $purchase_time = $request->purchase_time;
        $is_auto_renewing = $request->is_auto_renewing;


        //find the left battles,tournaments,tutorials of user
        try {
            $current_subscription = Subscriptions::where('id', $subscription_id)
                    ->first();

            if ($current_subscription->duration == 'until exhausted') {
                $battels_expiry_date = ' Battels remaining';
                $tutorials_expiry_date = ' Tutorials remaining';
                $tournament_expiry_date = ' Tournaments remaining';
                $expiry_date = $current_subscription->duration;
            } else {
                $expiry_date = date('Y-m-d', strtotime("+30 days"));
                $battels_expiry_date = ' Battles remaining untill ' . date('M jS,Y', strtotime($expiry_date));
                $tutorials_expiry_date = ' Tutorials remaining untill ' . date('M jS,Y', strtotime($expiry_date));
                $tournament_expiry_date = ' Tournaments remaining untill ' . date('M jS,Y', strtotime($expiry_date));
            }

            $exist_user_data = UserSubscriptions::where('is_cancelled', '=', 0)
                    ->where('user_id', '=', $user_id)
                    ->select('id', 'subscription_id', 'user_id', 'id', 'battle_left', 'tutorial_left', 'tournament_left', 'expiry_date')
                    ->first();

            /* If user data already exist */
            if (isset($exist_user_data)) {
                $old_subs_id = $exist_user_data->id;
                $package_id = $exist_user_data->subscription_id;
                $battle_left = $current_subscription->battles + $exist_user_data->battle_left;
                $tutorial_left = $current_subscription->tutorials + $exist_user_data->tutorial_left;
                $tournament_left = $current_subscription->tournaments + $exist_user_data->tournament_left;

                UserSubscriptions::where('id', $old_subs_id)->update(
                        ['is_cancelled' => 1]
                );
            } else {
                $battle_left = $current_subscription->battles;
                $tutorial_left = $current_subscription->tutorials;
                $tournament_left = $current_subscription->tournaments;
            }

            $user_subscription_id = UserSubscriptions::create([
                        'user_id' => $user_id,
                        'order_id' => $order_id,
                        'purchase_token' => $purchase_token,
                        'purchase_time' => $purchase_time,
                        'is_auto_renewing' => $is_auto_renewing,
                        'subscription_id' => $subscription_id,
                        'battle_left' => $battle_left,
                        'tutorial_left' => $tutorial_left,
                        'tournament_left' => $tournament_left,
                        'expiry_date' => $expiry_date,
                        'is_cancelled' => 0,
            ]);

            $exist_user_data = UserSubscriptions::where('id', $user_subscription_id->id)
                    ->select('id', 'subscription_id', 'user_id', 'id', 'battle_left', 'tutorial_left', 'tournament_left', 'expiry_date')
                    ->first();

            $user_subscriptions = [
                'user_subscription_id' => $user_subscription_id->id,
                'battles' => 'You have ' . $battle_left . $battels_expiry_date,
                'tutorials' => 'You have ' . $tutorial_left . $tutorials_expiry_date,
                'tournaments' => 'You have ' . $tournament_left . $tournament_expiry_date,
                'purchase_token' => $purchase_token,
                'expiry_date' => $expiry_date,
                'is_cancelled' => false,
            ];

            $data = [
                'subscription' => $current_subscription,
                'user_subscription' => $user_subscriptions
            ];
            return response()->json([
                        'code' => '200',
                        'message' => 'success',
                        'data' => $data
                            ], 200);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {get} /getusersubscriptionstatus user subscription list
     * @apiGroup User Subscription
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} user_subscription_id subscription id of user
     * @apiParamExample {json} Input
     *    {
     *      "user_subscription_id": 1,
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    
     *   {
     *  "code": "200",
     *   "message": "success",
     *   "data": {
     *      "subscription": {
     *          "id": 2,
     *          "SKU": "plan2",
     *          "tutorials": "5",
     *          "tournaments": "2",
     *          "battles": "2",
     *          "tournament_details": "Additional for $1.99",
     *          "battle_details": "Additional for $1.99",
     *          "tutorial_details": "Additional for $1.99",
     *          "name": "Monthly Plan",
     *          "duration": "per month",
     *          "price": 3.99,
     *          "created_at": "-0001-11-30 00:00:00",
     *          "modified_at": "0000-00-00 00:00:00"
     *          },
     *      "user_subscription": {
     *          "battles": "You have 2 Battles remaining untill Dec 16th,2017",
     *          "tutorials": "You have 5 Tutorials remaining untill Dec 16th,2017",
     *          "tournaments": "You have 2 Tournaments remaining untill Dec 16th,2017",
     *          "purchase_token": "token_1234",
     *          "expiry_date": "2017-12-16",
     *          "is_cancelled": false
     *           }
     *        }
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getUserSubscriptionStatus(Request $request)
    {
        $user_subscription_id = $request->user_subscription_id;
        try {
            $user_subscription = UserSubscriptions::where('id', $user_subscription_id)
                    ->where('is_cancelled', 0)
                    ->first();
            $current_subscription = Subscriptions::where('id', $user_subscription->subscription_id)
                    ->first();
            // if does not have get any value then null array will be return
            if (!$user_subscription) {
                $user_subscription = [];
                $current_subscription = [];
            } else {
                if ($user_subscription->expiry_date == 'until exhausted') {
                    $battels_expiry_date = ' Battels remaining';
                    $tutorials_expiry_date = ' Tutorials remaining';
                    $tournament_expiry_date = ' Tournaments remaining';
                } else {
                    $battels_expiry_date = ' Battles remaining untill ' . date('M jS,Y', strtotime($user_subscription->expiry_date));
                    $tutorials_expiry_date = ' Tutorials remaining untill ' . date('M jS,Y', strtotime($user_subscription->expiry_date));
                    $tournament_expiry_date = ' Tournaments remaining untill ' . date('M jS,Y', strtotime($user_subscription->expiry_date));
                }
                $user_subscriptions = [
                    'battles' => 'You have ' . $user_subscription->battle_left . $battels_expiry_date,
                    'tutorials' => 'You have ' . $user_subscription->tutorial_left . $tutorials_expiry_date,
                    'tournaments' => 'You have ' . $user_subscription->tournament_left . $tournament_expiry_date,
                    'purchase_token' => $user_subscription->purchase_token,
                    'expiry_date' => $user_subscription->expiry_date,
                    'is_cancelled' => (bool) $user_subscription->is_cancelled,
                ];
            }
            $data = [
                'subscription' => $current_subscription,
                'user_subscription' => $user_subscriptions
            ];
            return response()->json([
                        'code' => '200',
                        'message' => 'success',
                        'data' => $data
                            ], 200);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

    /**
     * @api {post} cancelsubscriptionapi cancel subscription for user
     * @apiGroup User Subscription
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParamExample {json} Input
     *    {
     *      "user_subscription_id": "1",
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of Subscription
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     * {
     *   "code": "200",
     *   "message": "success",
     *   "data": {
     *      "subscription": {
     *          "id": 2,
     *          "SKU": "plan2",
     *          "tutorials": "5",
     *          "tournaments": "2",
     *          "battles": "2",
     *          "tournament_details": "Additional for $1.99",
     *          "battle_details": "Additional for $1.99",
     *          "tutorial_details": "Additional for $1.99",
     *          "name": "Monthly Plan",
     *          "duration": "per month",
     *          "price": 3.99,
     *          "created_at": "-0001-11-30 00:00:00",
     *          "modified_at": "0000-00-00 00:00:00"
     *          },
     *      "user_subscription": {
     *          "battles": "You have 2 Battles remaining untill Dec 16th,2017",
     *          "tutorials": "You have 5 Tutorials remaining untill Dec 16th,2017",
     *          "tournaments": "You have 2 Tournaments remaining untill Dec 16th,2017",
     *          "purchase_token": "token_1234",
     *          "expiry_date": "2017-12-16",
     *          "is_cancelled": false
     *           }
     *        }
     *   }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function cancelSubscriptionAPI(Request $request)
    {
        $user_subscription_id = $request->user_subscription_id;
        try {
            $user_subscription = UserSubscriptions::where('id', $user_subscription_id)
                    ->where('is_cancelled', 0)
                    ->first();

            // if does not have get any value then null array will be return
            if (!$user_subscription) {
                $user_subscription = [];

                //if user does not have data so subcription id also we cant get so this array is null
                $current_subscription = [];
            } else {

                /* update is cancelled column in user subscription */
                UserSubscriptions::where('id', $user_subscription_id)
                        ->where('is_cancelled', 0)
                        ->update(['is_cancelled' => 1]);

                $current_subscription = Subscriptions::where('id', $user_subscription->subscription_id)
                        ->first();

                if ($user_subscription->expiry_date == 'until exhausted') {
                    $battels_expiry_date = ' Battels remaining';
                    $tutorials_expiry_date = ' Tutorials remaining';
                    $tournament_expiry_date = ' Tournaments remaining';
                } else {
                    $battels_expiry_date = ' Battles remaining untill ' . date('M jS,Y', strtotime($user_subscription->expiry_date));
                    $tutorials_expiry_date = ' Tutorials remaining untill ' . date('M jS,Y', strtotime($user_subscription->expiry_date));
                    $tournament_expiry_date = ' Tournaments remaining untill ' . date('M jS,Y', strtotime($user_subscription->expiry_date));
                }

                $user_subscription = [
                    'battles' => 'You had ' . $user_subscription->battle_left . $battels_expiry_date,
                    'tutorials' => 'You had ' . $user_subscription->tutorial_left . $tutorials_expiry_date,
                    'tournaments' => 'You had ' . $user_subscription->tournament_left . $tournament_expiry_date,
                    'purchase_token' => $user_subscription->purchase_token,
                    'expiry_date' => $user_subscription->expiry_date,
                    'is_cancelled' => (bool) 1,
                ];
            }
            $data = [
                'subscription' => $current_subscription,
                'user_subscription' => $user_subscription
            ];
            return response()->json([
                        'code' => '200',
                        'message' => 'success',
                        'data' => $data
                            ], 200);
        } catch (Exception $e) {
            return response()->json([
                        'error' => 'true',
                        'message' => 'Invalid request',
            ]);
        }
    }

}
