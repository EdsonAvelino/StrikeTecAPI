<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserSubscriptions;
use App\Subscriptions;

class UsersubscriptionController extends Controller
{

    /**
     * @api {post} /user/subscribe register user subscription details
     * @apiGroup User Subscription
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
        $userID = \Auth::user()->id;
        $subscriptionID = $request->subscription_id;
        $orderID = $request->order_id;
        $purchaseToken = $request->purchase_token;
        $purchaseTime = $request->purchase_time;
        $isAutoRenewing = $request->is_auto_renewing;


        //find the left battles,tournaments,tutorials of user
        try {
            $currentSubscription = Subscriptions::where('id', $subscriptionID)
                                            ->first();

            if ($currentSubscription->duration == 'until exhausted') {
                $battelsExpiryDate = ' Battels remaining';
                $tutorialsExpiryDate = ' Tutorials remaining';
                $tournamentExpiryDate = ' Tournaments remaining';
                $expiryDate = $currentSubscription->duration;
            } else {
                $expiryDate = date('Y-m-d', strtotime("+30 days"));
                $battelsExpiryDate = ' Battles remaining untill ' . date('M jS,Y', strtotime($expiryDate));
                $tutorialsExpiryDate = ' Tutorials remaining untill ' . date('M jS,Y', strtotime($expiryDate));
                $tournamentExpiryDate = ' Tournaments remaining untill ' . date('M jS,Y', strtotime($expiryDate));
            }

            $existUserData = UserSubscriptions::where('is_cancelled', '=', 0)
                    ->where('user_id', '=', $userID)
                    ->select('id', 'subscription_id', 'user_id', 'id', 'battle_left', 'tutorial_left', 'tournament_left', 'expiry_date')
                    ->first();

            /* If user data already exist */
            if (isset($existUserData)) {
                $oldSubsId = $existUserData->id;
                $packageID = $existUserData->subscription_id;
                $battleLeft = $currentSubscription->battles + $existUserData->battle_left;
                $tutorialLeft = $currentSubscription->tutorials + $existUserData->tutorial_left;
                $tournamentLeft = $currentSubscription->tournaments + $existUserData->tournament_left;

                UserSubscriptions::where('id', $oldSubsId)->update(
                        ['is_cancelled' => 1]
                );
            } else {
                $battleLeft = $currentSubscription->battles;
                $tutorialLeft = $currentSubscription->tutorials;
                $tournamentLeft = $currentSubscription->tournaments;
            }

            $userSubscriptionID = UserSubscriptions::create([
                        'user_id' => $userID,
                        'order_id' => $orderID,
                        'purchase_token' => $purchaseToken,
                        'purchase_time' => $purchaseTime,
                        'is_auto_renewing' => $isAutoRenewing,
                        'subscription_id' => $subscriptionID,
                        'battle_left' => $battleLeft,
                        'tutorial_left' => $tutorialLeft,
                        'tournament_left' => $tournamentLeft,
                        'expiry_date' => $expiryDate,
                        'is_cancelled' => 0,
            ]);

            $existUserData = UserSubscriptions::where('id', $userSubscriptionID->id)
                    ->select('id', 'subscription_id', 'user_id', 'id', 'battle_left', 'tutorial_left', 'tournament_left', 'expiry_date')
                    ->first();

            $userSubscriptions = [
                'user_subscription_id' => $userSubscriptionID->id,
                'battles' => 'You have ' . $battleLeft . $battelsExpiryDate,
                'tutorials' => 'You have ' . $tutorialLeft . $tutorialsExpiryDate,
                'tournaments' => 'You have ' . $tournamentLeft . $tournamentExpiryDate,
                'purchase_token' => $purchaseToken,
                'expiry_date' => $expiryDate,
                'is_cancelled' => false,
            ];

            $data = [
                'subscription' => $currentSubscription,
                'user_subscription' => $userSubscriptions
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
     * @api {get} /user/subscription user subscription status
     * @apiGroup User Subscription
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
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
        $userID = \Auth::user()->id;
        try {
            $userSubscription = UserSubscriptions::where('user_id', $userID)
                                                ->where('is_cancelled', 0)
                                                ->first();
            
            // if does not have get any value then null array will be return
            if (!$userSubscription) {
                $userSubscriptions = null;
                $currentSubscription = null;
            } else {
                $currentSubscription = Subscriptions::where('id', $userSubscription->subscription_id)
                                                     ->first();
                if ($userSubscription->expiry_date == 'until exhausted') {
                    $battelsExpiryDate = ' Battels remaining';
                    $tutorialsExpiryDate = ' Tutorials remaining';
                    $tournamentExpiryDate = ' Tournaments remaining';
                } else {
                    $battelsExpiryDate = ' Battles remaining untill ' . date('M jS,Y', strtotime($userSubscription->expiry_date));
                    $tutorialsExpiryDate = ' Tutorials remaining untill ' . date('M jS,Y', strtotime($userSubscription->expiry_date));
                    $tournamentExpiryDate = ' Tournaments remaining untill ' . date('M jS,Y', strtotime($userSubscription->expiry_date));
                }
                $userSubscriptions = [
                    'battles' => 'You have ' . $userSubscription->battle_left . $battelsExpiryDate,
                    'tutorials' => 'You have ' . $userSubscription->tutorial_left . $tutorialsExpiryDate,
                    'tournaments' => 'You have ' . $userSubscription->tournament_left . $tournamentExpiryDate,
                    'purchase_token' => $userSubscription->purchase_token,
                    'expiry_date' => $userSubscription->expiry_date,
                    'is_cancelled' => (bool) $userSubscription->is_cancelled,
                ];
            }
            $data = [
                'subscription' => $currentSubscription,
                'user_subscription' => $userSubscriptions
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
     * @api {post} /cancel/subscription cancel subscription for user
     * @apiGroup User Subscription
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded",
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
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
     *          "battles": "You had 2 Battles remaining untill Dec 16th,2017",
     *          "tutorials": "You had 5 Tutorials remaining untill Dec 16th,2017",
     *          "tournaments": "You had 2 Tournaments remaining untill Dec 16th,2017",
     *          "purchase_token": "token_1234",
     *          "expiry_date": "2017-12-16",
     *          "is_cancelled": true
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
    public function cancelSubscription(Request $request)
    {
        $userID = \Auth::user()->id;
        try {
            $userSubscription = UserSubscriptions::where('user_id', $userID)
                                                ->where('is_cancelled', 0)
                                                ->first();

            // if does not have get any value then null array will be return
            if (!$userSubscription) {
                $userSubscription = null;

                //if user does not have data so subcription id also we cant get so this array is null
                $currentSubscription = null;
            } else {

                /* update is cancelled column in user subscription */
                UserSubscriptions::where('user_id', $userID)
                        ->where('is_cancelled', 0)
                        ->update(['is_cancelled' => 1]);

                $currentSubscription = Subscriptions::where('id', $userSubscription->subscription_id)
                        ->first();

                if ($userSubscription->expiry_date == 'until exhausted') {
                    $battelsExpiryDate = ' Battels remaining';
                    $tutorialsExpiryDate = ' Tutorials remaining';
                    $tournamentExpiryDate = ' Tournaments remaining';
                } else {
                    $battelsExpiryDate = ' Battles remaining untill ' . date('M jS,Y', strtotime($userSubscription->expiry_date));
                    $tutorialsExpiryDate = ' Tutorials remaining untill ' . date('M jS,Y', strtotime($userSubscription->expiry_date));
                    $tournamentExpiryDate = ' Tournaments remaining untill ' . date('M jS,Y', strtotime($userSubscription->expiry_date));
                }

                $userSubscription = [
                    'battles' => 'You had ' . $userSubscription->battle_left . $battelsExpiryDate,
                    'tutorials' => 'You had ' . $userSubscription->tutorial_left . $tutorialsExpiryDate,
                    'tournaments' => 'You had ' . $userSubscription->tournament_left . $tournamentExpiryDate,
                    'purchase_token' => $userSubscription->purchase_token,
                    'expiry_date' => $userSubscription->expiry_date,
                    'is_cancelled' => (bool) 1,
                ];
            }
            $data = [
                'subscription' => $currentSubscription,
                'user_subscription' => $userSubscription
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
