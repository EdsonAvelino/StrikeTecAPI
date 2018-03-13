<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\IapPurchaseReceipts;
use App\IapProducts;

class IapController extends Controller
{
    /**
     * @api {post} /iap/receipt Store receipt data
     * @apiGroup In-App Purchases
     * @apiHeader {String} authorization Authorization value
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *       "Content-Type": "application/x-www-form-urlencoded",
     *     }
     * @apiParam {String="IOS","ANDROID"} platform App Platform iOS or Android
     * @apiParam {Number} product_id IAP Product ID
     * @apiParam {Number} order_id Order ID
     * @apiParam {Number} [purchase_token] purchase_token
     * @apiParam {json} receipt Json formatted receipt
     * @apiParamExample {json} Input
     * {
     *         "platform": "ios",
     *         "product_id": 1,
     *         "order_id": 12345678901234567890.1234567890123456,
     *         "receipt": "{
     *             "type": "android-playstore",
     *             "id": "12345678901234567890.1234567890123456",
     *             "purchaseToken": "purchase token goes here",
     *             "receipt": "{"orderId":"12345678901234567890.1234567890123456", "packageName":"com.example.app", "productId":"com.example.app.product", "purchaseTime":1417113074914, "purchaseState":0, "purchaseToken":"purchase token goes here"}",
     *              "signature": "signature data goes here"
     *          }",
     * }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Some data
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [ ]
     *      }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request: No receipt data found"
     *      }
     * @apiVersion 1.0.0
     */
    public function storeReceipt(Request $request)
    {
        // Valid receipt contains purchase token!
        $subscription = IapPurchaseReceipts::create([
            'user_id' => \Auth::id(),
            'platform' => $request->get('platform'),
            'iap_product_id' => $request->get('product_id'),
            'order_id' => $request->get('order_id'),
            'purchase_token' => $request->get('purchase_token'),
            'receipt' => $request->get('receipt'),
            'is_auto_renewing' => null, 
            'is_cancelled' => null
        ]);

        return response()->json(['error' => 'false', 'message' => '', 'data' => '...']);
    }

    /**
     * @api {get} /iap/products/{platform} Get IAP Products
     * @apiGroup In-App Purchases
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String="IOS","ANDROID"} platform App Platform iOS or Android
     * @apiParamExample {json} Input
     * {
     *         "platform": "IOS",
     * }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data Some data
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data": [
     *              {
     *                  "id": 1,
     *                  "category": "1",
     *                  "product_id": "trainee_monthly ",
     *                  "text": "$39.99",
     *                  "platform": "ANDROID"
     *              },
     *              {
     *                  "id": 2,
     *                  "category": "1",
     *                  "product_id": "trainee_yearly ",
     *                  "text": "$39.99",
     *                  "platform": "ANDROID"
     *              },
     *              {
     *                  "id": 3,
     *                  "category": "2",
     *                  "product_id": "coach_monthly ",
     *                  "text": "$39.99",
     *                  "platform": "ANDROID"
     *              },
     *              {
     *                  "id": 4,
     *                  "category": "3",
     *                  "product_id": "spectator_monthly ",
     *                  "text": "$39.99",
     *                  "platform": "ANDROID"
     *              },
     *              {
     *                  "id": 5,
     *                  "category": "3",
     *                  "product_id": "spectator_yearly ",
     *                  "text": "$39.99",
     *                  "platform": "ANDROID"
     *              }
     *          ]
     *      }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request: missing platform"
     *      }
     * @apiVersion 1.0.0
     */
    public function getProducts(Request $request, $platform)
    {
        $platform = strtoupper(trim($platform));

        if (!in_array($platform, ['ANDROID', 'IOS'])) {
            return response()->json(['error' => 'true', 'message' => 'Invalid request: missing platform']);
        }

        $products = IapProducts::where('platform', $platform)->get();
        
        return response()->json(['error' => 'true', 'message' => '', 'data' => $products]);
    }
}