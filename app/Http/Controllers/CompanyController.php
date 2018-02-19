<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Companies;

class CompanyController extends Controller
{
    /**
     * @api {get} /companies Get list of Companies
     * @apiGroup Fan User
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} companies List of Companies
     * @apiSuccessExample {json} Success
     *   HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data":[
     *          {
     *              "id": 1,
     *              "company_name": "EFD"
     *          },
     *          {
     *              "id": 2,
     *              "company_name": "Monster Energy"
     *          }
     *      ]
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
        $companyList = Companies::select('id', 'company_name', 'company_logo')->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $companyList]);
    }
}
