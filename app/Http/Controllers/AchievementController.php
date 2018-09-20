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
