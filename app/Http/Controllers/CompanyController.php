<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Companies;

class CompanyController extends Controller
{

    /**
     * @api {get}/companies Get list of Companies
     * @apiGroup Company
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Content-Type": "application/x-www-form-urlencoded"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} companies List of Companies
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data":[
     *                      {
     *                          "id": 1,
     *                          "company_name": "Normal"
     *                      },
     *                      {
     *                          "id": 2,
     *                          "company_name": "Monster Energy"
     *                      }
     *                  ]
     *  }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getCompanyList(Request $request)
    {
        $companyList = Companies::select('id', 'company_name')->get();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $companyList]);
    }


}
