<?php

namespace App\Http\Controllers;

use App\Cities;
use App\Countries;
use App\States;

class WorldController extends Controller
{
    /**
     * @api {get} /countries/<phase> Get countries
     * @apiGroup World
     * @apiParam {Number} phase List all countries belonging to the phase passed as parameter. Also includes countries with lower phases. If not phase is provided, all countries are returned
     * @apiParamExample {json} Input
     *    {
     *      "phase": 1,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of countries
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *         {
     *          "id": 1,
     *          "abbr": "AF",
     *          "name": "Afghanistan",
     *          "phone_code": 93
     *        },
     *        {
     *          "id": 2,
     *          "abbr": "AL",
     *          "name": "Albania",
     *          "phone_code": 355
     *        },
     *        {
     *          "id": 3,
     *          "abbr": "DZ",
     *          "name": "Algeria",
     *          "phone_code": 213
     *        },
     *        {
     *          "id": 4,
     *          "abbr": "AS",
     *          "name": "American Samoa",
     *          "phone_code": 1684
     *        },
     *        {
     *          "id": 5,
     *          "abbr": "AD",
     *          "name": "Andorra",
     *          "phone_code": 376
     *        },
     *        {
     *          "id": 6,
     *          "abbr": "AO",
     *          "name": "Angola",
     *          "phone_code": 244
     *        }
     *        ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getCountries($phase = null)
    {

        if ($phase) {
            $phase = (int) $phase;
            $countries = \App\Countries::where('phase', '<=', $phase)->get();
        } else {
            $countries = \App\Countries::get();
        }

        foreach ($countries as $country) {
            unset($country->phase);
        }


        return response()->json(['error' => 'false', 'message' => '', 'data' => $countries->toArray()]);
    }

    /**
     * @api {get} /states_by_country/<country_id> Get states of country
     * @apiGroup World
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of states by country
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *         {
     *          "id": 1,
     *          "country_id": 6,
     *          "name": "Bengo",
     *        },
     *        {
     *          "id": 2,
     *          "country_id": 6,
     *          "name": "Benguela",
     *        },
     *        {
     *          "id": 3,
     *          "country_id": 6,
     *          "name": "Bie",
     *        },
     *        {
     *          "id": 4,
     *          "country_id": 6,
     *          "name": "Cabinda",
     *        },
     *        {
     *          "id": 5,
     *          "country_id": 6,
     *          "name": "Cunene",
     *        },
     *        {
     *          "id": 6,
     *          "country_id": 6,
     *          "name": "Huambo",
     *        }
     *        ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getStatesByCountry($countryId)
    {
        $states = States::where('country_id', $countryId)->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $states->toArray()]);
    }

    /**
     * @api {get} /cities_by_state/<state_id> Get cities of state
     * @apiGroup World
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of cities by state
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *         {
     *          "id": 1,
     *          "state_id": 6,
     *          "name": "Amarpur",
     *        },
     *        {
     *          "id": 2,
     *          "state_id": 6,
     *          "name": "Ara",
     *        },
     *        {
     *          "id": 3,
     *          "state_id": 6,
     *          "name": "Araria",
     *        },
     *        {
     *          "id": 4,
     *          "state_id": 6,
     *          "name": "Asarganj",
     *        },
     *        {
     *          "id": 5,
     *          "state_id": 6,
     *          "name": "Aurangabad",
     *        },
     *        {
     *          "id": 6,
     *          "state_id": 6,
     *          "name": "Barh",
     *        }
     *        ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getCitiesByState($stateId)
    {
        $cities = Cities::where('state_id', $stateId)->get();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $cities->toArray()]);
    }
}
