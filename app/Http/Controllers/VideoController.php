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

    /**
     * Format to AA::BB:CC
     */
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
     * @api {get} /videos Get videos by category
     * @apiGroup Videos
     * @apiParam {Number} category_id Category Id e.g. 1 = Workout Routines, 2 = Tutorials, 3 = Drills, 4 = Essentials
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "category_id": 1,
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
     *              "title": "Sample Video",
     *              "file": "SampleVideo_1280x720_10mb.mp4",
     *              "view_counts": 250,
     *              "author_name": "Limer Waughts",
     *              "duration": "00:01:02"
     *          },
     *          {
     *              "id": 2,
     *              "title": "Another Sample Video",
     *              "file": "SampleVideo_1280x720_20mb.mp4",
     *              "view_counts": 360,
     *              "author_name": "Aeron Emeatt",
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
        $categoryId = (int) $request->get('category_id') ?? 0;

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $videos = Videos::where('category_id', $categoryId)->offset($offset)->limit($limit)->get();

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos->toArray()]);
    }

    /**
     * @api {get} /videos/search Search videos
     * @apiGroup Videos
     * @apiParam {String} q Search term e.g. "boxing+stance+and+footwork"
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "q": "boxing+stance+and+footwork",
     *      "start": 0,
     *      "limit": 10,
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
     *              "title": "Sample Video",
     *              "file": "SampleVideo_1280x720_10mb.mp4",
     *              "view_counts": 250,
     *              "author_name": "Limer Waughts",
     *              "duration": "00:01:02"
     *          },
     *          {
     *              "id": 2,
     *              "title": "Another Sample Video",
     *              "file": "SampleVideo_1280x720_20mb.mp4",
     *              "view_counts": 360,
     *              "author_name": "Aeron Emeatt",
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
    public function searchVideos(Request $request)
    {
        $query = str_replace('+', ' ', $request->get('q'));

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $queryBreaks = preg_split('/\s+/', $query);
        array_walk($queryBreaks, [$this, 'alterParam']);
        
        $videos = Videos::where('title', 'like', (str_replace('+', '%', $request->get('q'))) );

        foreach ($queryBreaks as $queryBreak) {
            $videos->orWhere('title', 'like', $queryBreak);
        }

        $videos = $videos->offset($offset)->limit($limit)->get();

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos->toArray()]);
    }

    /**
     * Alter param
     */
    private function alterParam(&$param)
    {
        $param = "%$param%";
    }

    /**
     * @api {post} /videos/favourite/{videoId} Add video to Favourite
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
     * @api {post} /videos/unfavourite/{videoId} Remove video from Favourite
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
     * @api {post} /videos/add_view/{videoId} Add view_counts to video
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
