<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\IapPurchaseReceipts;
use App\IapProducts;

class IapController extends Controller
{
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