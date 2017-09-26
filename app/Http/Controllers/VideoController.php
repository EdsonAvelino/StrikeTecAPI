<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Videos;
use App\UserFavVideos;

class VideoController extends Controller
{
    /**
     * Sync Videos
     */
    public function syncVideos()
    {
        $dirVideos = new \DirectoryIterator(storage_path('videos'));
        
        $videos = [];

        foreach ($dirVideos as $videoFile) {
            if (!$videoFile->isDot()) {
                $getID3 = new \getID3;
                $videoInfo = $getID3->analyze(storage_path('videos/'.$videoFile));

                $video = Videos::firstOrCreate(['file' => $videoFile]);
                
                $video->duration = $this->formatDuration($videoInfo['playtime_string']);
                $video->view_counts = 0;
                $video->save();
            }
        }

        return null;
    }

    // Format to AA::BB:CC
    private function formatDuration($duration)
    {
        // The base case is A:BB
        if ( strlen($duration) == 4 ) {
            return "00:0" . $duration;
        }
        // If AA:BB
        else if ( strlen($duration) == 5 ) {
            return "00:" . $duration;
        }
        // If A:BB:CC
        else if ( strlen($duration) == 7 ) {
            return "0" . $duration;
        }
    }
    
    /**
     * @api {get} /videos Get list of videos
     * @apiGroup Videos
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} videos List of videos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "videos": [
     *          {
     *              "id": 1,
     *              "title": null,
     *              "file": "SampleVideo_1280x720_10mb.mp4",
     *              "view_counts": 0,
     *              "duration": "00:01:02"
     *          },
     *          {
     *              "id": 2,
     *              "title": null,
     *              "file": "SampleVideo_1280x720_20mb.mp4",
     *              "view_counts": 0,
     *              "duration": "00:01:27"
     *          },
     *      ]
     *    }
     * @apiErrorExample {json} Login error (Invalid credentials)
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getVideos(Request $request)
    {
        $offset = (int) $request->get('start') ?? 0;
        $limit = (int) $request->get('limit') ?? 20;

        $videos = Videos::offset($offset)->limit($limit)->get();

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos->toArray()]);
    }

    /**
     * @api {get} /videos/favourite/{videoId} Add video to Favourite
     * @apiGroup Videos
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Successfully saved in favourite list",
     *    }
     * @apiErrorExample {json} Error
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function setVideoFav($videoId)
    {
        $videoId = (int) $videoId;

        if ($videoId) {
            $userFavVideo = UserFavVideos::firstOrCreate(['user_id' => \Auth::user()->id, 'video_id' => $videoId]);

            return response()->json(['error' => 'false', 'message' => 'Successfully saved in favourite list']);
        }
    }

    /**
     * @api {get} /videos/unfavourite/{videoId} Remove video from Favourite
     * @apiGroup Videos
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Successfully removed from favourite list",
     *    }
     * @apiErrorExample {json} Error
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function setVideoUnFav($videoId)
    {
        $videoId = (int) $videoId;

        if ($videoId) {
            UserFavVideos::where('user_id', \Auth::user()->id)->where('video_id',  $videoId)->delete();

            return response()->json(['error' => 'false', 'message' => 'Successfully removed from favourite list']);
        }
    }

    /**
     * @api {get} /videos/add_view/{videoId} Add view_counts to video
     * @apiGroup Videos
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "Added successfully",
     *    }
     * @apiErrorExample {json} Error
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function addViewCount($videoId)
    {
        $videoId = (int) $videoId;

        if ($videoId) {
            $video = Videos::find($videoId);
            
            $video->view_counts = $video->view_counts + 1;
            $video->save();

            return response()->json(['error' => 'false', 'message' => 'Added successfully']);
        }
    }

    /**
     * @api {get} /user/fav_videos Get user's videos
     * @apiGroup Videos
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50,
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} videos List of videos
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "videos": [
     *          {
     *              "id": 1,
     *              "title": null,
     *              "file": "SampleVideo_1280x720_10mb.mp4",
     *              "view_counts": 0,
     *              "duration": "00:01:02"
     *          },
     *          {
     *              "id": 2,
     *              "title": null,
     *              "file": "SampleVideo_1280x720_20mb.mp4",
     *              "view_counts": 0,
     *              "duration": "00:01:27"
     *          },
     *      ]
     *    }
     * @apiErrorExample {json} Login error (Invalid credentials)
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getUserFavVideos(Request $request)
    {
        $offset = (int) $request->get('start') ?? 0;
        $limit = (int) $request->get('limit') ?? 20;

        $userId = \Auth::user()->id;
        $videos = Videos::whereRaw("id IN (SELECT video_id from user_fav_videos WHERE user_id = $userId)")->offset($offset)->limit($limit)->get();

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos->toArray()]);
    }
}
