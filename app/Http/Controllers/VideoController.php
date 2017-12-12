<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Videos;
use App\UserFavVideos;
use App\Tags;
use App\VideoCategory;

class VideoController extends Controller
{
    /**
     * @api {get} /videos Get videos by category
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} category_id Category Id e.g. 1 = Workout Routines, 2 = Tutorials, 3 = Drills, 4 = Essentials 
     * @apiParam {String} [tag_id] Tag Ids separated by comma for eg:1,2,3 or 1
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "tag_id": 1,
     *      "category_id": 1,
     *      "start": 0,
     *      "limit": 10
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
     *              "file": "http://example.com/videos/SampleVideo_1280x720_10mb.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/SampleVideo_1280x720_10mb.png",
     *              "view_counts": 250,
     *              "author_name": "Limer Waughts",
     *              "duration": "00:01:02",
     *              "user_favourited": true,
     *              "thumb_width": 342,
     *              "thumb_height": 185
     *          },
     *          {
     *              "id": 2,
     *              "title": "Another Sample Video",
     *              "file": "https://youtu.be/ScMzIvxBSi4",
     *              "thumbnail": "http://example.com/videos/thumb/ScMzIvxBSi4.png",
     *              "view_counts": 360,
     *              "author_name": "Aeron Emeatt",
     *              "duration": "00:01:27",
     *              "user_favourited": false,
     *              "thumb_width": 342,
     *              "thumb_height": 185
     *          },
     *      ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getVideos(Request $request)
    {
        $categoryId = (int) $request->get('category_id') ? $request->get('category_id') : 0;
        $tagId = $request->get('tag_id');
        $offset = (int) $request->get('start') ? $request->get('start') : 0;
        $limit = (int) $request->get('limit') ? $request->get('limit') : 20;
        if ($tagId) {
            $tags = explode(',', $tagId);
            $_videos = Videos::select(['*', 'thumbnail as thumb_width', 'thumbnail as thumb_height'])
                            ->join('tagged_videos', 'videos.id', '=', 'tagged_videos.video_id')
                            ->join('tags', 'tags.id', '=', 'tagged_videos.tag_id')
                            ->whereIn('tagged_videos.tag_id', $tags)
                            ->where('videos.category_id', $categoryId)->groupBy('videos.id')
                            ->offset($offset)->limit($limit)->get();
        } else {
            $_videos = Videos::select(['*', 'thumbnail as thumb_width', 'thumbnail as thumb_height'])
                            ->where('category_id', $categoryId)
                            ->offset($offset)->limit($limit)->get();
        }
        $videos = [];

        foreach ($_videos as $video) {
            $userFavourited = UserFavVideos::where('user_id', \Auth::user()->id)->where('video_id', $video->id)->exists();

            $video['user_favourited'] = $userFavourited;

            $videos[] = $video;
        }

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos]);
    }

    /**
     * @api {get} /videos/search Search videos
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {String} query Search term e.g. "boxing+stance+and+footwork"
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of videos
     * @apiParamExample {json} Input
     *    {
     *      "query": "boxing+stance+and+footwork",
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
     *              "file": "http://example.com/videos/SampleVideo_1280x720_10mb.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/SampleVideo_1280x720_10mb.png",
     *              "view_counts": 250,
     *              "author_name": "Limer Waughts",
     *              "duration": "00:01:02",
     *              "user_favourited": true,
     *              "thumb_width": 342,
     *              "thumb_height": 185
     *          },
     *          {
     *              "id": 2,
     *              "title": "Another Sample Video",
     *              "file": "https://youtu.be/ScMzIvxBSi4",
     *              "thumbnail": "http://example.com/videos/thumb/ScMzIvxBSi4.png",
     *              "view_counts": 360,
     *              "author_name": "Aeron Emeatt",
     *              "duration": "00:01:27",
     *              "user_favourited": false,
     *              "thumb_width": 342,
     *              "thumb_height": 185
     *          },
     *      ]
     *    }
     * @apiErrorExample {json} Error Response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function searchVideos(Request $request)
    {
        $query = str_replace('+', ' ', $request->get('query'));

        $offset = (int) ($request->get('start') ?? 0);
        $limit = (int) ($request->get('limit') ?? 20);

        $queryBreaks = preg_split('/\s+/', $query);
        array_walk($queryBreaks, [$this, 'alterParam']);

        $videos = Videos::select(['*', 'thumbnail as thumb_width', 'thumbnail as thumb_height'])->where('title', 'like', (str_replace('+', '%', $request->get('q'))));

        foreach ($queryBreaks as $queryBreak) {
            $videos->orWhere('title', 'like', $queryBreak);
        }

        $_videos = $videos->offset($offset)->limit($limit)->get();

        $videos = [];

        foreach ($_videos as $video) {
            $userFavourited = UserFavVideos::where('user_id', \Auth::user()->id)->where('video_id', $video->id)->exists();

            $video['user_favourited'] = $userFavourited;

            $videos[] = $video;
        }

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos]);
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
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
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
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
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
            UserFavVideos::where('user_id', \Auth::user()->id)->where('video_id', $videoId)->delete();

            return response()->json(['error' => 'false', 'message' => 'Successfully removed from favourite list']);
        }
    }

    /**
     * @api {post} /videos/add_view/{videoId} Add view_counts to video
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
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
     * @api {get} /user/fav_videos Get user's fav videos
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
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
     *              "file": "http://example.com/videos/SampleVideo_1280x720_10mb.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/SampleVideo_1280x720_10mb.png",
     *              "view_counts": 250,
     *              "author_name": "Limer Waughts",
     *              "duration": "00:01:02",
     *              "user_favourited": true
     *          },
     *          {
     *              "id": 2,
     *              "title": null,
     *              "file": "http://example.com/videos/SampleVideo_1280x720_20mb.mp4",
     *              "thumbnail": "http://example.com/videos/thumb/SampleVideo_1280x720_20mb.png",
     *              "view_counts": 170,
     *              "author_name": "Aeron Emeatt",
     *              "duration": "00:01:12",
     *              "user_favourited": true
     *          },
     *      ]
     *    }
     * @apiErrorExample {json} Error Response
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
        $_videos = Videos::whereRaw("id IN (SELECT video_id from user_fav_videos WHERE user_id = $userId)")->offset($offset)->limit($limit)->get();

        $videos = [];

        foreach ($_videos as $video) {
            $userFavourited = UserFavVideos::where('user_id', \Auth::user()->id)->where('video_id', $video->id)->exists();

            $video['user_favourited'] = $userFavourited;

            $videos[] = $video;
        }

        return response()->json(['error' => 'false', 'message' => '', 'videos' => $videos]);
    }

    /**
     * @api {get}/videos/tags list of video's tags
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of video's tags
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data":[
     *                      {
     *                          "id": 1,
     *                          "type": 1,
     *                          "name": "Boxing Videos"
     *                      },
     *                      {
     *                          "id": 2,
     *                          "type": 1,
     *                          "activity_name": "Kickboxing Videos"
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
    public function getVideoTags(Request $request)
    {
        $tagList = Tags::getTags(1);
        return response()->json(['error' => 'false', 'message' => '', 'data' => $tagList]);
    }

    /**
     * @api {get}/videos/category Get list of videos categories
     * @apiGroup Videos
     * @apiHeader {String} authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of videos categories
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *   {
     *      "error": "false",
     *      "message": "",
     *      "data":[
     *                      {
     *                          "id": 1,
     *                          "name": "Workout Routines"
     *                      },
     *                      {
     *                          "id": 2,
     *                          "name": "Tutorials"
     *                      }
     *                      {
     *                          "id": 3,
     *                          "name": "Drills"
     *                      }
     *                      {
     *                          "id": 4,
     *                          "name": "Essentials"
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
    public function getVideoCat(Request $request)
    {
        $catList = VideoCategory::all();
        return response()->json(['error' => 'false', 'message' => '', 'data' => $catList]);
    }

}
