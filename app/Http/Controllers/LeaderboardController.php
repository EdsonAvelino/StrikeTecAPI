<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Leaderboard;

class LeaderboardController extends Controller
{
	/**
     * @api {get} /leaderboard Get leaderboard data
     * @apiGroup Leaderboard
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} [country_id] Filter by country, no country_id will return data across all countries
     * @apiParam {Number} [state_id] Filter by state
     * @apiParam {Number} [age] Age range e.g. 25-40
     * @apiParam {Number} [weight] Weight range e.g. 90-120
     * @apiParam {String="male","female"} [gender] Gender
     * @apiParamExample {json} Input
     *    {
     *      "country_id": 1,
     *      "state_id": 25,
     *      "age": 21-30
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of leaderboard users
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *		"data": [
	 *		  {
	 *			"id": 1,
	 *			"user_id": 7,
	 *			"sessions_count": 10,
	 *			"avg_speed": 207,
	 *			"avg_force": 4011,
	 *			"punches_count": 1088,
	 *			"max_speed": 312,
	 *			"max_force": 5714,
	 *			"total_time_trained": 2100,
	 *			"created_at": "2017-10-04 20:04:38",
	 *			"updated_at": "2017-10-04 20:04:38",
	 *			"rank": 1,
	 *			"user": {
	 *				"id": 7,
	 *				"first_name": "Joun",
	 *				"last_name": "Smith",
	 *				"skill_level": "Box like a Professional",
	 *				"weight": 200,
	 *				"age": 24,
	 *				"user_following": false,
	 *				"user_follower": false
	 *				"photo_url": null,
	 *				"gender": "female",
	 *				"country": {
	 *					"id": 155,
	 *					"name": "Netherlands The"
	 *				},
	 *				"state": {
	 *					"id": 2594,
	 *					"country_id": 155,
	 *					"name": "Noord-Holland"
	 *				},
	 *				"city": {
	 *					"id": 30199,
	 *					"state_id": 2594,
	 *					"name": "Haarlem"
	 *				}
	 *			}
	 *		},
	 *		{
	 *			"id": 3,
	 *			"user_id": 9,
	 *			"sessions_count": 8,
	 *			"avg_speed": 165,
	 *			"avg_force": 3304,
	 *			"punches_count": 2637,
	 *			"max_speed": 240,
	 *			"max_force": 4233,
	 *			"total_time_trained": 1500,
	 *			"created_at": "2017-10-04 20:04:38",
	 *			"updated_at": "2017-10-04 20:04:38",
	 *			"rank": 2,
	 *			"user": {
	 *				"id": 9,
	 *				"first_name": "Jack",
	 *				"last_name": "Carrie",
	 *				"skill_level": null,
	 *				"weight": 108,
	 *				"age": 24,
	 *				"user_following": false,
	 *				"user_follower": false
	 *				"photo_url": null,
	 *				"gender": "female",
	 *				"country": {
	 *					"id": 155,
	 *					"name": "Netherlands The"
	 *				},
	 *				"state": {
	 *					"id": 2594,
	 *					"country_id": 155,
	 *					"name": "Noord-Holland"
	 *				},
	 *				"city": {
	 *					"id": 30199,
	 *					"state_id": 2594,
	 *					"name": "Haarlem"
	 *				}
	 *			}
	 *		}
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getList(Request $request)
    {
    	// \DB::enableQueryLog();

    	$countryId = (int) $request->get('country_id');
    	$stateId = (int) $request->get('state_id');
    	
    	$gender = $request->get('gender');
    	$gender = (in_array($gender, ['male', 'female'])) ? $gender : null;

    	$age = $request->get('age');
    	$weight = $request->get('weight');

    	$ageRange = ($age) ? explode('-', $age) : [];
    	$weightRange = ($weight) ? explode('-', $weight) : [];

        $limit = 100;

		\DB::statement(\DB::raw('SET @rank = 0'));

		// Get ranks first, so that we can know requesting user's rank
		$_leadersRanksList = Leaderboard::select('user_id', \DB::raw('@rank:=@rank+1 AS rank'))
			->orderBy('punches_count', 'desc');
		
		if ($countryId) {
			$_leadersRanksList->whereHas('user', function ($query) use ($countryId) {
				$query->where('country_id', $countryId);
			});
		}

		$currentUserRank = $this->getCurrentUserRank($_leadersRanksList->get()->toArray());
		
		// If current user's in top 100, will return result
		if ($currentUserRank <= 100) {
			\DB::statement(\DB::raw('SET @rank = 0'));

			$leadersList = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', 'city_id', 'state_id', 'country_id', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender')
                	->with(['country', 'state', 'city']);
            }])
        	->whereHas('user', function($query) use ($countryId, $stateId, $ageRange, $weightRange, $gender) {
        		if ($countryId) {
	    			$query->where('country_id', $countryId);

	    			// State (can be null when no country selected)
	    			if ($stateId)
	    				$query->where('state_id', $stateId);
	    		}

	    		if (sizeof($ageRange)) {
	            	$query->whereRaw('get_age(birthday, NOW()) between ? AND ?', $ageRange);
	            }

	            if (sizeof($weightRange)) {
	            	$query->whereBetween('weight', $weightRange);
	            }

	            if ($gender) {
	            	$query->where('gender', $gender);
	            }
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
				$q->orWhere('user_preferences.user_id', \Auth::user()->id);
        	})
        	->select('*', \DB::raw('@rank:=@rank+1 AS rank'))
        	->orderBy('punches_count', 'desc')
        	->limit(100)->get()->toArray();
		} 
		// Else, will break down current result set to get current user's rank is in list
		// e.g., if current user's rank is 500, then return 1 to 50 and 475 to 525
		else {
			// First set of result, showing top 50
			\DB::statement(\DB::raw('SET @rank = 0'));
			$leadersListFirstSet = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', 'city_id', 'state_id', 'country_id', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender', 'city_id', 'state_id', 'country_id')
                	->with(['country', 'state', 'city']);
            }])
        	->whereHas('user', function($query) use ($countryId, $stateId, $ageRange, $weightRange, $gender) {
        		if ($countryId) {
	    			$query->where('country_id', $countryId);

	    			// State (can be null when no country selected)
	    			if ($stateId)
	    				$query->where('state_id', $stateId);
	    		}

	    		if (sizeof($ageRange)) {
	            	$query->whereRaw('get_age(birthday, NOW()) between ? AND ?', $ageRange);
	            }

	            if (sizeof($weightRange)) {
	            	$query->whereBetween('weight', $weightRange);
	            }

	            if ($gender) {
	            	$query->where('gender', $gender);
	            }
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
				$q->orWhere('user_preferences.user_id', \Auth::user()->id);
        	})
        	->select('*', \DB::raw('@rank:=@rank+1 AS rank'))
        	->orderBy('punches_count', 'desc')
        	->limit(50)->get();

        	// Another set of result, this will include current user
        	\DB::statement(\DB::raw('SET @rank = ' . ($currentUserRank - 25) ));
        	$leadersListSecondSet = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', 'city_id', 'state_id', 'country_id', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender')
                	->with(['country', 'state', 'city']);
            }])
        	->whereHas('user', function($query) use ($countryId, $stateId, $ageRange, $weightRange, $gender) {
        		if ($countryId) {
	    			$query->where('country_id', $countryId);

	    			// State (can be null when no country selected)
	    			if ($stateId)
	    				$query->where('state_id', $stateId);
	    		}

	    		if (sizeof($ageRange)) {
	            	$query->whereRaw('get_age(birthday, NOW()) between ? AND ?', $ageRange);
	            }

	            if (sizeof($weightRange)) {
	            	$query->whereBetween('weight', $weightRange);
	            }

	            if ($gender) {
	            	$query->where('gender', $gender);
	            }
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
				$q->orWhere('user_preferences.user_id', \Auth::user()->id);
        	})
        	->select('*', \DB::raw('@rank:=@rank+1 AS rank'))
        	->orderBy('punches_count', 'desc')
        	->offset(($currentUserRank - 25))->limit(50)->get();

        	$leadersList = array_merge($leadersListFirstSet->toArray(), $leadersListSecondSet->toArray());
		}

		// ->orderByRaw('(user_id = '. \Auth::user()->id .') desc')
        // dd(\DB::getQueryLog());

        return response()->json(['error' => 'false', 'message' => '', 'data' => $leadersList]);
    }

    /**
     * @api {get} /explore Get Explore data
     * @apiGroup Leaderboard
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number records
     * @apiParam {Number} [country_id] Filter by country, no country_id will return data across all countries
     * @apiParam {Number} [state_id] Filter by state
     * @apiParam {Number} [age] Age range e.g. 25-40
     * @apiParam {Number} [weight] Weight range e.g. 90-120
     * @apiParam {String="male","female"} [gender] Gender
     * @apiParam {String} [search] Query string e.g. john, result will be of all users having name "john"
     * @apiParamExample {json} Input
     *    {
     *      "country_id": 1,
     *      "state_id": 25,
     *      "age": 21-30
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of leaderboard users
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *		"data": [
	 *		  {
	 *			"id": 1,
	 *			"user_id": 7,
	 *			"sessions_count": 10,
	 *			"avg_speed": 207,
	 *			"avg_force": 4011,
	 *			"punches_count": 1088,
	 *			"max_speed": 312,
	 *			"max_force": 5714,
	 *			"total_time_trained": 2100,
	 *			"created_at": "2017-10-04 20:04:38",
	 *			"updated_at": "2017-10-04 20:04:38",
	 *			"rank": 1,
	 *			"user": {
	 *				"id": 7,
	 *				"first_name": "Joun",
	 *				"last_name": "Smith",
	 *				"skill_level": "Box like a Professional",
	 *				"weight": 200,
	 *				"age": 24,
	 *				"user_following": false,
	 *				"user_follower": false
	 *				"photo_url": null,
	 *				"gender": "female",
	 *				"number_of_challenges": 9,
	 *				"country": {
	 *					"id": 155,
	 *					"name": "Netherlands The"
	 *				},
	 *				"state": {
	 *					"id": 2594,
	 *					"country_id": 155,
	 *					"name": "Noord-Holland"
	 *				},
	 *				"city": {
	 *					"id": 30199,
	 *					"state_id": 2594,
	 *					"name": "Haarlem"
	 *				}
	 *			}
	 *		},
	 *		{
	 *			"id": 3,
	 *			"user_id": 9,
	 *			"sessions_count": 8,
	 *			"avg_speed": 165,
	 *			"avg_force": 3304,
	 *			"punches_count": 2637,
	 *			"max_speed": 240,
	 *			"max_force": 4233,
	 *			"total_time_trained": 2800,
	 *			"created_at": "2017-10-04 20:04:38",
	 *			"updated_at": "2017-10-04 20:04:38",
	 *			"rank": 2,
	 *			"user": {
	 *				"id": 9,
	 *				"first_name": "Jack",
	 *				"last_name": "Carrie",
	 *				"skill_level": null,
	 *				"weight": null,
	 *				"age": 24,
	 *				"user_following": false,
	 *				"user_follower": false
	 *				"photo_url": null,
	 *				"gender": "female",
	 *				"number_of_challenges": 5,
	 *				"country": {
	 *					"id": 155,
	 *					"name": "Netherlands The"
	 *				},
	 *				"state": {
	 *					"id": 2594,
	 *					"country_id": 155,
	 *					"name": "Noord-Holland"
	 *				},
	 *				"city": {
	 *					"id": 30199,
	 *					"state_id": 2594,
	 *					"name": "Haarlem"
	 *				}
	 *			}
	 *		}
     *      ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getExploreList(Request $request)
    {
    	// \DB::enableQueryLog();

    	$countryId = (int) $request->get('country_id');
    	$stateId = (int) $request->get('state_id');
    	
    	$gender = $request->get('gender');
    	$gender = (in_array($gender, ['male', 'female'])) ? $gender : null;

    	$age = $request->get('age');
    	$weight = $request->get('weight');

    	$ageRange = ($age) ? explode('-', $age) : [];
    	$weightRange = ($weight) ? explode('-', $weight) : [];

    	// Query to search user by name
    	$searchQuery = $request->get('search') ?? null;

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

		\DB::statement(\DB::raw('SET @rank = 0'));

		$leadersList = Leaderboard::with(['user' => function ($query) {
            $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', 'city_id', 'state_id', 'country_id', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender', \DB::raw('id as number_of_challenges'))
            	->with(['country', 'state', 'city']);
        }])
    	->whereHas('user', function($query) use ($countryId, $stateId, $ageRange, $weightRange, $gender, $searchQuery) {
    		if ($countryId) {
    			$query->where('country_id', $countryId);

    			// State (can be null when no country selected)
    			if ($stateId)
    				$query->where('state_id', $stateId);
    		}
    		
    		if (sizeof($ageRange)) {
            	$query->whereRaw('get_age(birthday, NOW()) between ? AND ?', $ageRange);
            }

            if (sizeof($weightRange)) {
            	$query->whereBetween('weight', $weightRange);
            }

            if ($gender) {
            	$query->where('gender', $gender);
            }

            if ($searchQuery) {
            	$query->where(function ($q) use ($searchQuery) {
            		$name = explode(' ', str_replace('+', ' ', $searchQuery));
            		
            		if (count($name) > 1){
            			$q->where('first_name', 'like', "%$name[0]%")->orWhere('last_name', 'like', "%$name[1]%");
            		} else {
            			$q->where('first_name', 'like', "%$name[0]%")->orWhere('last_name', 'like', "%$name[0]%");
            		}
            		
            	});
            }
    	})
    	->whereHas('user.preferences', function($q) {
			$q->where('public_profile', 1);
    	})
    	->select('*', \DB::raw('@rank:=@rank+1 AS rank'))
    	->orderBy('punches_count', 'desc')
    	->offset($offset)->limit($limit)->get()->toArray();
		
		// ->orderByRaw('(user_id = '. \Auth::user()->id .') desc')
        // dd(\DB::getQueryLog());

        return response()->json(['error' => 'false', 'message' => '', 'data' => $leadersList]);
    }

    // Get current user's rank in leaderboard
    private function getCurrentUserRank($list)
	{
	   foreach ($list as $row) {
	      if ( $row['user_id'] === \Auth::user()->id )
	         return $row['rank'];
	   }

	   return null;
	}
}
