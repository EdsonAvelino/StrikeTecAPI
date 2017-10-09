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
     * @apiParam {Number} [country_id] Filter by country, no country_id will return users across all countries
     * @apiParamExample {json} Input
     *    {
     *      "country_id": 1,
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
	 *			"avg_time": "0.00",
	 *			"created_at": "2017-10-04 20:04:38",
	 *			"updated_at": "2017-10-04 20:04:38",
	 *			"rank": 1,
	 *			"user": {
	 *				"id": 7,
	 *				"first_name": "Joun",
	 *				"last_name": "Smith",
	 *				"skill_level": "Box like a Professional",
	 *				"weight": 200,
	 *				"country_id": null,
	 *				"state_id": null,
	 *				"city_id": null,
	 *				"age": 14,
	 *				"user_following": true,
	 *				"user_follower": false
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
	 *			"avg_time": "0.00",
	 *			"created_at": "2017-10-04 20:04:38",
	 *			"updated_at": "2017-10-04 20:04:38",
	 *			"rank": 2,
	 *			"user": {
	 *				"id": 9,
	 *				"first_name": "Jack",
	 *				"last_name": "Carrie",
	 *				"skill_level": null,
	 *				"weight": null,
	 *				"country_id": null,
	 *				"state_id": null,
	 *				"city_id": null,
	 *				"age": null,
	 *				"user_following": false,
	 *				"user_follower": false
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
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender')
                	->with(['country', 'state', 'city']);
            }])
        	->whereHas('user', function($query) use ($countryId) {
        		if ($countryId) {
        			$query->where('country_id', $countryId);
        		}
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
        	})
        	->select('*', \DB::raw('@rank:=@rank+1 AS rank'))
        	->orderBy('punches_count', 'desc')
        	->limit(100)->get()->toArray();
		} 
		// Else, will break down current result set to get current user's rank in list
		// So, if current user's rank is 500, then return 1 to 50 and 475 to 525
		else {
			// First set of result, showing top 50
			\DB::statement(\DB::raw('SET @rank = 0'));
			$leadersListFirstSet = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender', 'city_id', 'state_id', 'country_id')
                	->with(['country', 'state', 'city']);
            }])
        	->whereHas('user', function($query) use ($countryId) {
        		if ($countryId) {
        			$query->where('country_id', $countryId);
        		}
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
        	})
        	->select('*', \DB::raw('@rank:=@rank+1 AS rank'))
        	->orderBy('punches_count', 'desc')
        	->limit(50)->get();

        	// Another set of result, this will include current user
        	\DB::statement(\DB::raw('SET @rank = ' . ($currentUserRank - 25) ));
        	$leadersListSecondSet = Leaderboard::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'skill_level', 'weight', \DB::raw('birthday as age'), \DB::raw('id as user_following'), \DB::raw('id as user_follower'), 'photo_url', 'gender')
                	->with(['country', 'state', 'city']);
            }])
        	->whereHas('user', function($query) use ($countryId) {
        		if ($countryId) {
        			$query->where('country_id', $countryId);
        		}
        	})
        	->whereHas('user.preferences', function($q) {
				$q->where('public_profile', 1);
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

    private function getCurrentUserRank($list)
	{
	   foreach($list as $row) {
	      if ( $row['user_id'] === \Auth::user()->id )
	         return $row['rank'];
	   }

	   return null;
	}
}
