<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Leaderboard;
use App\GameLeaderboard;

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

    	if(empty($request->get('limit')))
        	$limit = 50;
        else
        	$limit = $request->get('limit');

        if(empty($request->get('start')))
        	$start = 0;
        else
        	$start = $request->get('start');

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
		//if ($currentUserRank <= 100) {
			\DB::statement(\DB::raw('SET @rank = 0'));

			$leadersList = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', 'city_id', 'state_id', 'country_id', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender')
                	->with(['country', 'state', 'city']);
            }])
            //->where('avg_speed', '>', '8')->where('avg_force', '>', 100)
            ->where('punches_count', '>', 0)
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

	            /*$query->where(function($q) {
	            	$q->whereNull('is_spectator')->orWhere('is_spectator', 0);
	            });*/
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
				$q->orWhere('user_preferences.user_id', \Auth::user()->id);
        	})
        	->select('leaderboard.id','leaderboard.user_id','leaderboard.sessions_count','leaderboard.avg_speed','leaderboard.avg_force','leaderboard.punches_count','leaderboard.max_speed','leaderboard.max_force',\DB::raw('floor(leaderboard.total_time_trained/1000) as total_time_trained'),'leaderboard.last_training_date','leaderboard.total_days_trained', \DB::raw('@rank:=@rank+1 AS rank'))
        	//->orderBy('punches_count', 'desc')
        	->orderBy('punches_count','desc')
        	//->offset($start)->limit($limit)->get()->toArray();
        	->get()->toArray();

        	
        	$leadersList = $this->showCurrentUserFirst($leadersList,'user_id',\Auth::user()->id);

        	$leadersList = array_splice($leadersList,$start,$limit);	



        	//->get()->toArray();
		/*} 
		// Else, will break down current result set to get current user's rank is in list
		// e.g., if current user's rank is 500, then return 1 to 50 and 475 to 525
		else {
			// First set of result, showing top 50
			\DB::statement(\DB::raw('SET @rank = 0'));
			$leadersListFirstSet = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', 'city_id', 'state_id', 'country_id', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender', 'city_id', 'state_id', 'country_id')
                	->with(['country', 'state', 'city']);
            }])
            //->where('avg_speed', '>', '8')->where('avg_force', '>', 100)
             ->where('punches_count', '>', 0)
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

	            //$query->where(function($q) {
	            //	$q->whereNull('is_spectator')->orWhere('is_spectator', 0);
	            //});
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
				$q->orWhere('user_preferences.user_id', \Auth::user()->id);
        	})
        	->select('leaderboard.id','leaderboard.user_id','leaderboard.sessions_count','leaderboard.avg_speed','leaderboard.avg_force','leaderboard.punches_count','leaderboard.max_speed','leaderboard.max_force',\DB::raw('floor(leaderboard.total_time_trained/1000) as total_time_trained'),'leaderboard.last_training_date','leaderboard.total_days_trained', \DB::raw('@rank:=@rank+1 AS rank'))
        	->orderBy('punches_count','desc')
        	->offset($start)->limit($limit)->get();
        	//->get();

        	// Another set of result, this will include current user
        	\DB::statement(\DB::raw('SET @rank = ' . ($currentUserRank - 25) ));
        	$leadersListSecondSet = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', 'city_id', 'state_id', 'country_id', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender')
                	->with(['country', 'state', 'city']);
            }])
            //->where('avg_speed', '>', '8')->where('avg_force', '>', 100)
            ->where('punches_count', '>', 0)
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

	            $query->where(function($q) {
	            	$q->whereNull('is_spectator')->orWhere('is_spectator', 0);
	            });
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
				$q->orWhere('user_preferences.user_id', \Auth::user()->id);
        	})
        	->select('leaderboard.id','leaderboard.user_id','leaderboard.sessions_count','leaderboard.avg_speed','leaderboard.avg_force','leaderboard.punches_count','leaderboard.max_speed','leaderboard.max_force',\DB::raw('floor(leaderboard.total_time_trained/1000) as total_time_trained'),'leaderboard.last_training_date','leaderboard.total_days_trained', \DB::raw('@rank:=@rank+1 AS rank'))
        	->orderBy('punches_count','desc')
        	->offset(($currentUserRank - 25))->limit($limit)->get();

        	$leadersList = array_merge($leadersListFirstSet->toArray(), $leadersListSecondSet->toArray());
        	$leadersList = $this->showCurrentUserFirst($leadersList,'user_id',\Auth::user()->id);
		}*/

		// ->orderByRaw('(user_id = '. \Auth::user()->id .') desc')
        // dd(\DB::getQueryLog());

        return response()->json(['error' => 'false', 'message' => '', 'data' => $leadersList]);
    }

    function showCurrentUserFirst($array, $key, $value){

		$finalArr = array();
		$k = 1;
		foreach($array as $subKey => $subArray){
			if($subArray[$key] == $value){
				$finalArr[0] = $array[$subKey];
				unset($array[$subKey]);
			}
			else {
				$finalArr[$k] = $subArray;
				$k++;
			}
		}
		ksort($finalArr);

		return $finalArr;
	}

    /**
     * @api {get} /trending Get Trending data
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
    public function getTrendingList(Request $request)
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

		// filter with session count this week.
		// but will change per day when we have lots of users
		
		$leadersList = Leaderboard::select('leaderboard.id','leaderboard.user_id','leaderboard.sessions_count','leaderboard.avg_speed','leaderboard.avg_force','leaderboard.punches_count','leaderboard.max_speed','leaderboard.max_force',\DB::raw('floor(leaderboard.total_time_trained/1000) as total_time_trained'),'leaderboard.last_training_date','leaderboard.total_days_trained', 'week_sessions.week_sessions_count')
			->leftJoin(\DB::raw('( SELECT user_id, COUNT(*) AS "week_sessions_count" FROM `sessions` WHERE YEARWEEK(FROM_UNIXTIME(start_time / 1000), 1) = YEARWEEK(CURDATE(), 1) GROUP BY user_id ) week_sessions'),  function($join) {
	           		$join->on('leaderboard.user_id', '=', 'week_sessions.user_id');
	        })->with(['user' => function ($query) {
	            $query->select(
	            	'id',
	            	'first_name',
	            	'last_name',
	            	'skill_level',
	            	'weight',
	            	'city_id',
	            	'state_id',
	            	'country_id',
	            	\DB::raw('birthday as age'),
	            	\DB::raw('id as user_following'),
	            	\DB::raw('id as user_follower'),
	            	'photo_url',
	            	'gender',
	            	\DB::raw('id as number_of_challenges')
	            )->with(['country', 'state', 'city']);
	        }])
	        //->where('avg_speed', '>', '8')->where('avg_force', '>', 100)
	        ->where('sessions_count', '>', 0)
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

	            $query->where(function($q) {
	            	$q->whereNull('is_spectator')->orWhere('is_spectator', 0);
	            });
	        
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
	    	// ->where('leaderboard.user_id', '!=', \Auth::id())
	    	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
	    	})
	    	->orderBy('week_sessions_count', 'desc')
	    	->orderBy('sessions_count', 'desc')
	    	->offset($offset)->limit($limit)->get();
	
	    foreach ($leadersList as $idx => $row) {
	    	$row->rank = $offset + $idx + 1;
	    }

	 
        return response()->json(['error' => 'false', 'message' => '', 'data' => $leadersList]);
    }

    /**
     * @api {get} /leaderboard/game Game leaderboard data
     * @apiGroup Game
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number="1=Reaction Time Game", "2=Speed", "3=Endurance", "4=Power Game"} game_id Game Id
     * @apiParamExample {json} Input
     *    {
     *      "game_id": 1,
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
     *			  {
     *			      "id": 473,
     *			      "user_id": 473,
     *			      "game_id": 1,
     *			      "score": 98.81,
     *			      "distance": 19,
     *			      "user": {
     *			          "id": 473,
     *			          "first_name": "Eduard",
     *			          "last_name": "Logesdale",
     *			          "skill_level": null,
     *			          "weight": 147,
     *			          "age": 28,
     *			          "user_following": false,
     *			          "user_follower": false,
     *			          "photo_url": "https://robohash.org/aliquidautrepudiandae.png?size=50x50&set=set1",
     *			          "gender": "male",
     *			          "country": null,
     *			          "state": null,
     *			          "city": null
     *			      }
     *			  },
     *			  {
     *			      "id": 956,
     *			      "user_id": 956,
     *			      "game_id": 1,
     *			      "score": 98.77,
     *			      "distance": 15,
     *			      "user": {
     *			          "id": 956,
     *			          "first_name": "Meris",
     *			          "last_name": "Patrickson",
     *			          "skill_level": null,
     *			          "weight": 179,
     *			          "age": 18,
     *			          "user_following": false,
     *			          "user_follower": false,
     *			          "photo_url": "https://robohash.org/cumquequoddolor.png?size=50x50&set=set1",
     *			          "gender": "female",
     *			          "country": null,
     *			          "state": null,
     *			          "city": null
     *			      }
     *			  },
     *			  {
     *			      "id": 958,
     *			      "user_id": 958,
     *			      "game_id": 1,
     *			      "score": 98.41,
     *			      "distance": 10,
     *			      "user": {
     *			          "id": 958,
     *			          "first_name": "Shepherd",
     *			          "last_name": "Oulett",
     *			          "skill_level": null,
     *			          "weight": 170,
     *			          "age": 34,
     *			          "user_following": false,
     *			          "user_follower": false,
     *			          "photo_url": "https://robohash.org/officiavoluptasaccusantium.bmp?size=50x50&set=set1",
     *			          "gender": "male",
     *			          "country": null,
     *			          "state": null,
     *			          "city": null
     *			      }
     *			  }
     *			]
     *      }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getGameLeaderboardData(Request $request)
    {
    	$gameId = (int) $request->get('game_id');
    	$limit = 50;

    	\DB::statement(\DB::raw('SET @rank = 0'));

    	$dataUserRanks = GameLeaderboard::select('user_id', \DB::raw('@rank:=@rank+1 AS rank'));
    	
    	if ($gameId == 1) {
        	$dataUserRanks->orderBy('score', 'asc')->orderBy('distance', 'desc');
        } else {
        	$dataUserRanks->orderBy('score', 'desc');
        }

        $currentUserRank = $this->getCurrentUserRank($dataUserRanks->get()->toArray());

        // Reset rank to get actual data with rank
        \DB::statement(\DB::raw('SET @rank = 0'));

    	$dataStmt = GameLeaderboard::select('*', \DB::raw('@rank:=@rank+1 AS rank'))
    		->with(['user' => function ($query) {
                $query->select([
                	'id',
                	'first_name',
                	'last_name',
                	'skill_level',
                	'weight',
                	'city_id',
                	'state_id',
                	'country_id',
                	\DB::raw('birthday as age'),
                	\DB::raw('id as user_following'),
                	\DB::raw('id as user_follower'),
                	'photo_url',
                	'gender'
                ])->with(['country', 'state', 'city']);
            }])->where('game_id', $gameId)->limit($limit);

        if ($gameId == 1) {
        	$dataStmt->orderBy('score', 'asc')->orderBy('distance', 'desc');
        } else {
        	$dataStmt->orderBy('score', 'desc');
        }

        if ($currentUserRank <= 50) {
        	$data = $dataStmt->get()->toArray();
        } else {
        	$dataListOne = $dataStmt->limit(25)->get()->toArray();
        	$dataListTwo = $dataStmt->offset(($currentUserRank - 12))->limit(25)->get()->toArray();

        	$data = array_merge($dataListOne, $dataListTwo);
        }

        foreach ($data as $i => $raw) {
        	switch ($gameId) {
        		case 1: $data[$i]['score'] = (float) number_format($raw['score'], 3); break; // Reaction time
        		case 2: $data[$i]['score'] = (int) $raw['score']; break;
        		case 3: $data[$i]['score'] = (int) $raw['score']; break;
        		case 4: $data[$i]['score'] = (int) $raw['score']; break;
        	}
        	
        	$data[$i]['distance'] = (float) number_format($raw['distance'], 1);
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);
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
