<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Achievements;
use App\AchievementTypes;
use App\UserAchievements;

class AchievementController extends Controller
{
    /**
     * @api {get} /achievements Get list of achievements
     * @apiGroup Achievements
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} Achievement's information
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "false",
     *          "message": "",
     *          "data":  [
     *                   {
     *                       "achievement_id": 1,
     *                       "achievement_name": "belts",
     *                       "badges": [
     *                           {
     *                               "id": null,
     *                               "achievement_name": "belts",
     *                               "badge_name": "Belts",
     *                               "description": "User will get his badge when won battle 5 times in a row.",
     *                               "image": "http://img.example.com/badges/Champion.png",
     *                               "badge_value": 0,
     *                               "min": 0,
     *                               "max": 0,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           }
     *                       ]
     *                   },
     *                   {
     *                       "achievement_id": 8,
     *                       "achievement_name": "Champion",
     *                       "badges": [
     *                           {
     *                               "id": null,
     *                               "achievement_name": "Champion",
     *                               "badge_name": "Champion",
     *                               "description": "Champion",
     *                               "image": "http://img.example.com/badges/Champion.png",
     *                               "badge_value": 1,
     *                               "min": 0,
     *                               "max": 0,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           }
     *                       ]
     *                   },
     *                   {
     *                       "achievement_id": 4,
     *                       "achievement_name": "Accomplish 100% of goal",
     *                       "badges": [
     *                           {
     *                               "id": null,
     *                               "achievement_name": "Accomplish 100% of goal",
     *                               "badge_name": "Goal",
     *                               "description": "User can earn several badges for this like belt.\nIf user accomplish goal, then count will be increased, user can earn only one badge for each\ngoal.",
     *                               "image": "http://img.example.com/badges/Accomplish_100.png",
     *                               "badge_value": 1,
     *                               "min": 0,
     *                               "max": 0,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           }
     *                       ]
     *                   },
     *                   {
     *                       "achievement_id": 12,
     *                       "achievement_name": "Iron First",
     *                       "badges": [
     *                           {
     *                               "id": null,
     *                               "achievement_name": "Iron First",
     *                               "badge_name": "Bronze",
     *                               "description": "Strong Man",
     *                               "image": "http://img.example.com/badges/Iron_Fist_1.png",
     *                               "badge_value": 0,
     *                               "min": 600,
     *                               "max": 700,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           },
     *                           {
     *                               "id": null,
     *                               "achievement_name": "Iron First",
     *                               "badge_name": "Silver",
     *                               "description": "Iron Fist â€“ Single punch over 600lbs for male â€“ 400lbs for female - ",
     *                               "image": "http://img.example.com/badges/Iron_Fist_2.png",
     *                               "badge_value": 0,
     *                               "min": 701,
     *                               "max": 800,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           },
     *                           {
     *                               "id": null,
     *                               "achievement_name": "Iron First",
     *                               "badge_name": "Gold",
     *                               "description": "User Participation",
     *                               "image": "http://img.example.com/badges/Iron_Fist_1.png",
     *                               "badge_value": 0,
     *                               "min": 801,
     *                               "max": 1250,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           }
     *                       ]
     *                   },
     *                   {
     *                       "achievement_id": 7,
     *                       "achievement_name": "User Participation",
     *                       "badges": [
     *                           {
     *                               "id": null,
     *                               "achievement_name": "User Participation",
     *                               "badge_name": "Bronze",
     *                               "description": "Speed Demon â€“ if user has speed average over 20mph for more than 10 training sessions",
     *                               "image": "http://img.example.com/badges/User_Participation.png",
     *                               "badge_value": 0,
     *                               "min": 1,
     *                               "max": 5,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           },
     *                           {
     *                               "id": null,
     *                               "achievement_name": "User Participation",
     *                               "badge_name": "Silver",
     *                               "description": "Iron Fist â€“ Single punch over 600lbs for male â€“ 400lbs for female - ",
     *                               "image": "http://img.example.com/badges/User_Participation.png",
     *                               "badge_value": 0,
     *                               "min": 6,
     *                               "max": 15,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           },
     *                           {
     *                               "id": null,
     *                               "badge_name": "Gold",
     *                               "achievement_name": "User Participation",
     *                               "description": "User Participation",
     *                               "image": "http://img.example.com/badges/User_Participation.png",
     *                               "badge_value": 0,
     *                               "min": 16,
     *                               "max": 50,
     *                               "count": 0,
     *                               "shared": false,
     *                               "awarded": false
     *                           }
     *                       ]
     *                   },
     *                   {
     *                       "achievement_id": 9,
     *                       "achievement_name": "Accuracy",
     *                       "badges": [
     *                           {
     *                               "id": null,
     *                               "achievement_name": "Accuracy",
     *                               "badge_name": "Bronze",
     *                               "description": "Speed Demon â€“ if user has speed average over 20mph for more than 10 training sessions",
     *                               "image": "http://img.example.com/badges/Accuracy.png",
     *                               "badge_value": 0,
     *                               "min": 10,
     *                               "max": 10,
     *                               "shared": false,
     *                               "count": 0,
     *                               "awarded": false
     *                           },
     *                           {
     *                               "id": null,
     *                               "badge_name": "Silver",
     *                               "achievement_name": "Accuracy",
     *                               "description": "Iron Fist â€“ Single punch over 600lbs for male â€“ 400lbs for female - ",
     *                               "image": "http://img.example.com/badges/Accuracy.png",
     *                               "badge_value": 0,
     *                               "min": 11,
     *                               "max": 25,
     *                               "shared": false,
     *                               "count": 0,
     *                               "awarded": false
     *                           },
     *                       ]
     *                   }
     *               ]
     *      }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Error message what problem is..."
     *      }
     * @apiVersion 1.0.0
     */
    public function getAchievementList(Request $request)
    {
        $userId = \Auth::user()->id;
        $gender = \Auth::user()->gender;

        if ($gender == NULL) {
            $gender = 'male';
        }
        
        $achievements = Achievements::with('achievementType')->orderBy('sequence')->get();
        $userAchievements = UserAchievements::where('user_id', $userId)->get()->keyBy('achievement_type_id')->toArray();

        foreach ($achievements as $checkData) {
            $achievementType = $checkData['achievementType'];
            $resultFinalData = [];
            $resultFinalData['achievement_id'] = $checkData['id'];
            $resultFinalData['achievement_name'] = $checkData['name'];
            $resultFinalData['badges'] = [];
            
            foreach ($achievementType as $data) {
                if ($checkData['id'] == 12) {
                    if ($data['gender'] != $gender) {
                        continue;
                    }
                }

                $count = 0;
                $userBadgeValue = $data['config'];
                $shared = FALSE;
                $awarded = FALSE;
                $userBadgeID = NULL;
                $achievementTypeID = $data['id'];

                if (isset($userAchievements[$achievementTypeID])) {
                    $count = $userAchievements[$achievementTypeID]['count'];
                    $userBadgeID = $userAchievements[$achievementTypeID]['id'];
                    $userBadgeValue = $userAchievements[$achievementTypeID]['metric_value'];
                    $shared = filter_var($userAchievements[$achievementTypeID]['shared'], FILTER_VALIDATE_BOOLEAN);
                    $awarded = filter_var($userAchievements[$achievementTypeID]['awarded'], FILTER_VALIDATE_BOOLEAN);
                }

                $resultData['id'] = $userBadgeID;
                $resultData['achievement_name'] = $checkData['name'];
                $resultData['badge_name'] = $data['name'];
                $resultData['description'] = $data['description'];
                $resultData['image'] = $data['image'];
                $resultData['badge_value'] = $userBadgeValue;
                $resultData['min'] = $data['min'];
                $resultData['max'] = $data['max'];
                $resultData['count'] = $count;
                $resultData['shared'] = $shared;
                $resultData['awarded'] = $awarded;

                $resultFinalData['badges'][] = $resultData;
            }

            $result[] = $resultFinalData;
        }

        return response()->json([
            'error' => 'false',
            'message' => '',
            'data' => $result,
        ]);
    }

}
