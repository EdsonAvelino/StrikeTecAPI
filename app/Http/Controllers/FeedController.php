<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Posts;
use App\PostLikes;
use App\PostComments;

class FeedController extends Controller
{

    /**
     * @api {get} /feed/posts Get Feed-Posts
     * @apiGroup Feed
     * @apiHeader {String} Authorization Authorization value
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} start Start offset
     * @apiParam {Number} limit Limit number of records
     * @apiParamExample {json} Input
     *    {
     *      "start": 20,
     *      "limit": 50
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccess {Object} data List of feed-posts
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "",
     *      "data": [
     *        {
     *            "id": 6,
     *            "post_type_id": 1,
     *            "data_id": 117,
     *            "title": "da cheng shared his battle history with Qiang Hu",
     *            "text": null,
     *            "created_at": 1511546127,
     *            "likes_count": 0,
     *            "comments_count": 0,
     *            "user": {
     *                "id": 20,
     *                "first_name": "da",
     *                "last_name": "cheng",
     *                "photo_url": null,
     *                "user_following": true,
     *                "user_follower": false
     *                "points": 99
     *            },
     *            "user_likes": false
     *            "extra_data": "{
     *                  \"winner_total_win_counts\":5,
     *                  \"loser_total_win_counts\":2,
     *                  \"winner\": {
     *                      \"id\": 20,
     *                      \"first_name\": \"da\",
     *                      \"last_name\": \"cheng\",
     *                      \"photo_url\": null,
     *                      \"user_following\": true,
     *                      \"user_follower\": true,
     *                      \"points\": 518,
     *                      \"avg_speed\": 21,
     *                      \"avg_force\": 583,
     *                      \"max_speed\": 29,
     *                      \"max_force\": 948,
     *                      \"best_time\": 0.50,
     *                      \"punches_count\": 10
     *                  },
     *                  \"loser\": {
     *                      \"id\": 7,
     *                      \"first_name\": \"Qiang\",
     *                      \"last_name\": \"Hu\",
     *                      \"photo_url\": null,
     *                      \"user_following\": false,
     *                      \"user_follower\": false,
     *                      \"points\": 2308,
     *                      \"avg_speed\": 20,
     *                      \"avg_force\": 575,
     *                      \"max_speed\": 29,
     *                      \"max_force\": 948,
     *                      \"best_time\": 0.50,
     *                      \"punches_count\": 10
     *                  }"
     *             }
     *        },
     *        {
     *            "id": 3,
     *            "post_type_id": 2,
     *            "data_id": 1,
     *            "title": "John Smith shared a training session",
     *            "text": null,
     *            "created_at": 1511299855,
     *            "likes_count": 0,
     *            "comments_count": 0,
     *            "user": {
     *                "id": 16,
     *                "first_name": "John",
     *                "last_name": "Smith",
     *                "photo_url": null,
     *                "user_following": false,
     *                "user_follower": false
     *                "points": 125
     *            },
     *            "user_likes": false,
     *            "extra_data": "{
     *                  \"punches_count\": 25,
     *                  \"avg_speed\": 20,
     *                  \"avg_force\": 217
     *              }"
     *        },
     *        {
     *            "id": 13,
     *            "post_type_id": 3,
     *            "data_id": 18,
     *            "title": "Steve Johns has accomplished goal",
     *            "text": null,
     *            "created_at": 1512592159,
     *            "likes_count": 0,
     *            "comments_count": 0,
     *            "user": {
     *                "id": 1,
     *                "first_name": "Steve",
     *                "last_name": "Johns",
     *                "photo_url": null,
     *                "user_following": false,
     *                "user_follower": false,
     *                "points": 80
     *            },
     *            "extra_data": "{
     *                     \"id\":18,
     *                     \"activity_id\":1,
     *                     \"activity_type_id\":1,
     *                     \"target\":\"25\",
     *                     \"start_date\":1511116200,
     *                     \"end_date\":1511720999,
     *                     \"followed\":0,
     *                     \"followed_date\":1511438605,
     *                     \"done_count\":0,
     *                     \"avg_time\":0,
     *                     \"avg_speed\":0,
     *                     \"avg_power\":0,
     *                     \"achieve_type\":0,
     *                     \"shared\":0
     *             }",
     *             "user_likes": false
     *        },
     *        {
     *            "id": 8,
     *            "post_type_id": 4,
     *            "data_id": 18,
     *            "title": "Steve Johns has won Belt.",
     *            "text": null,
     *            "created_at": 1512592159,
     *            "likes_count": 0,
     *            "comments_count": 0,
     *            "user": {
     *                "id": 1,
     *                "first_name": "Steve",
     *                "last_name": "Johns",
     *                "photo_url": null,
     *                "user_following": false,
     *                "user_follower": false,
     *                "points": 80
     *            },
     *            "extra_data": "{
     *                     \"achievement_id\": 1,
     *                     \"achievement_name\": \"belts\",
     *                     \"badge_name\": \"Belts\",
     *                     \"description\": \"Belts\",
     *                     \"image\": \"http://54.233.233.189/storage/badges/Champion.png\",
     *                     \"badge_value\": 0,
     *                     \"awarded\": false,
     *                     \"count\": 0,
     *                     \"shared\": false
     *             }",
     *             "user_likes": false
     *        },
     *    ]
     *    }
     * @apiErrorExample {json} Error response
     *    HTTP/1.1 200 OK
     *      {
     *          "error": "true",
     *          "message": "Invalid request"
     *      }
     * @apiVersion 1.0.0
     */
    public function getPosts(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        // \DB::enableQueryLog();
        // Feed-Post data
        $posts = [];
        // Feed-Posts from DB
        $_posts = Posts::with(['user' => function($q) {
                                $q->select(['id', 'first_name', 'last_name', 'photo_url', 'gender', \DB::raw('id as user_following'), \DB::raw('id as user_follower'), \DB::raw('id as points')]);
                            }])
                                ->whereRaw('user_id IN (SELECT follow_user_id as "user_id" FROM user_connections WHERE user_id = ?)', [\Auth::user()->id])
                                ->orWhere('user_id', \Auth::user()->id)
                                ->withCount('likes')->withCount('comments')
                                ->offset($offset)->limit($limit)->orderBy('created_at', 'desc')->get();

                // dd(\DB::getQueryLog());

                foreach ($_posts as $post) {
                    $_post = $post->toArray();

                    $user1FullName = $post->user->first_name . ' ' . $post->user->last_name;

                    $user2FullName = null;

                    $_post['extra_data'] = "";

                    switch ($post->post_type_id) {
                        case 1:
                            if ($post->data->user_id == $post->user_id) {
                                $user2FullName = $post->data->opponentUser->first_name . ' ' . $post->data->opponentUser->last_name;
                            } else {
                                $user2FullName = $post->data->user->first_name . ' ' . $post->data->user->last_name;
                            }

                            // extra_data contains feed type related data battle, training, goal etc
                            $extraData = [];
                            $battleResult = \App\Battles::getResult($post->data_id);

                            $winnerTotalWinCounts = 0;
                            $loserTotalWinCounts = 0;

                            if (!is_null($battleResult['winner']) && !is_null($battleResult['loser'])) {
                                $winnerUserId = $battleResult['winner']['id'];
                                $loserUserId = $battleResult['loser']['id'];

                                $winnerTotalWinCounts = \App\Battles::where('winner_user_id', $winnerUserId)->count();
                                $loserTotalWinCounts = \App\Battles::where(function($query) use($loserUserId) {
                                            $query->where('user_id', $loserUserId)->orWhere('opponent_user_id', $loserUserId);
                                        })->where('winner_user_id', $loserUserId)->count();
                            }

                            $extraData['winner_total_win_counts'] = $winnerTotalWinCounts;
                            $extraData['loser_total_win_counts'] = $loserTotalWinCounts;

                            $extraData = array_merge($extraData, $battleResult);
                            $_post['extra_data'] = json_encode($extraData);
                            break;

                        case 2:
                            // avg punch count, avg speed, avg power.
                            $extraData = [];
                            $extraData['punches_count'] = $post->data->punches_count;
                            $extraData['avg_speed'] = $post->data->avg_speed;
                            $extraData['avg_force'] = $post->data->avg_force;
                            $_post['extra_data'] = json_encode($extraData);
                            break;
                        
                        case 3:
                            $goalData = \App\Goals::select('id', 'activity_id', 'activity_type_id', 'target', \DB::raw('UNIX_TIMESTAMP(start_at) as start_date'), \DB::raw('UNIX_TIMESTAMP(end_at) as end_date'), 'followed', \DB::raw('UNIX_TIMESTAMP(followed_at) as followed_date'), 'done_count', 'avg_time', 'avg_speed', 'avg_power', 'achieve_type', 'shared')
                                            ->where('id', $post->data_id)->first();
                           
                            $_post['extra_data'] = json_encode($goalData);
                            break;
                        
                        case 4:
                            $achievement = \App\SharedAchievements::find($post->data_id);
                            $_post['extra_data'] = $achievement->achievement_data;
                            break;
                    }

                    $userTemplate = (strtolower($post->user->gender) == 'female') ? 'her' : 'his';

                    $_post['title'] = str_replace(['_USER1_', '_TEMPLATE_', '_USER2_'], [$user1FullName, $userTemplate, $user2FullName], $post->title);

                    $userLikes = PostLikes::where('post_id', $post->id)->where('user_id', \Auth::user()->id)->exists();

                    $_post['user_likes'] = (bool) $userLikes;

                    $posts[] = $_post;
                }

                return response()->json(['error' => 'false', 'message' => '', 'data' => $posts]);
            }

            /**
             * @api {post} /feed/posts Add new Feed-Post (Share on feed)
             * @apiGroup Feed
             * @apiHeader {String} Content-Type application/x-www-form-urlencoded
             * @apiHeader {String} Authorization Authorization token
             * @apiHeaderExample {json} Header-Example:
             *     {
             *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
             *     }
             * @apiParam {Number="1 = Finished Battle", "2 = Training", "3 = Accomplished Goal",  "4 = Badge"} post_type_id Feed-Post type 
             * @apiParam {Number} data_id ID of what user is sharing, battle/trounament etc
             * @apiParam {String} [text] Post text to share with feed-post
             * @apiParamExample {json} Input
             *    {
             *      "post_type_id": 1,
             *      "data_id": 1
             *      "text": "Post text"
             *    }
             * @apiSuccess {Boolean} error Error flag 
             * @apiSuccess {String} message Error message
             * @apiSuccessExample {json} Success
             *    HTTP/1.1 200 OK
             *    {
             *      "error": "false",
             *      "message": "Shared on feed",
             *    }
             * @apiErrorExample {json} Error response
             *    HTTP/1.1 200 OK
             *      {
             *          "error": "true",
             *          "message": "Invalid request"
             *      }
             * @apiVersion 1.0.0
             */
            public function addPost(Request $request)
            {
                $data = null;
                $shared = 'shared';
                $dataId = (int) $request->get('data_id');
                // Battle
                if ($request->get('post_type_id') == 1) {
                    $data = \App\Battles::where('id', $request->get('data_id'))->first();

                    if ($data && $data->user_id == \Auth::user()->id) {
                        $shared = 'user_shared';
                    } elseif ($data && $data->opponent_user_id == \Auth::user()->id) {
                        $shared = 'opponent_shared';
                    }
                }
                // Training session
                elseif ($data &&  $request->get('post_type_id') == 2) {
                    $data = \App\Sessions::where('id', $request->get('data_id'))->first();
                }
                // Goal
                elseif ( $data &&  $request->get('post_type_id') == 3) {
                    $data = \App\Goals::where('id', $request->get('data_id'))->first();
                }
                // Badge
                elseif ($data &&  $request->get('post_type_id') == 4) {
                    $data = \App\UserAchievements::where('id', $request->get('data_id'))->first();
                    $sharedDataJson = \App\UserAchievements::get($data->id);
                    $sharedData = json_encode($sharedDataJson);
                    $sharedData = \App\SharedAchievements::create([
                                'achievement_data' => $sharedData
                    ]);
                    $dataId = $sharedData->id;
                }
                else{
                    return response()->json(['error' => 'false', 'message' => 'Data Not Found']);

                }

                if ($data && !(filter_var($data->{$shared}, FILTER_VALIDATE_BOOLEAN))) {
                    $post = Posts::create([
                                'user_id' => \Auth::user()->id,
                                'post_type_id' => (int) $request->get('post_type_id'),
                                'data_id' => $dataId,
                                'text' => $request->get('text')
                    ]);

                    $data->{$shared} = 1;
                    $data->save();
                }

                return response()->json(['error' => 'false', 'message' => 'Shared on feed']);
            }

            /**
             * @api {post} /feed/posts/<post_id>/like Like feed-post
             * @apiGroup Feed
             * @apiHeader {String} Authorization Authorization token
             * @apiHeaderExample {json} Header-Example:
             *     {
             *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
             *     }
             * @apiParam {Number} post_id Feed Post type
             * @apiParamExample {json} Input
             *    {
             *      "post_id": 12,
             *    }
             * @apiSuccess {Boolean} error Error flag 
             * @apiSuccess {String} message Error message
             * @apiSuccessExample {json} Success
             *    HTTP/1.1 200 OK
             *    {
             *      "error": "false",
             *      "message": "Liked",
             *    }
             * @apiErrorExample {json} Error response
             *    HTTP/1.1 200 OK
             *      {
             *          "error": "true",
             *          "message": "Invalid request"
             *      }
             * @apiVersion 1.0.0
             */
            public function postLike($postId)
            {
                try {

                    $postId = (int) $postId;
                    $post = Posts::find($postId);

                    if ($post &&
                        !(PostLikes::where('post_id', $postId)->where('user_id', \Auth::user()->id)->exists())
                    ) {

                        PostLikes::create([
                            'post_id' => $postId,
                            'user_id' => \Auth::user()->id,
                        ]);

                        if ($post->user_id != \Auth::user()->id) {
                            // Generates new notification for user
                            \App\UserNotifications::generate(\App\UserNotifications::FEED_POST_LIKE, $post->user_id, \Auth::user()->id, $postId);
                        }
                        return response()->json(['error' => 'false', 'message' => 'Liked']);

                    }else{
                        return response()->json(['error' => 'true', 'message' => 'Post not found or post already liked']);

                    }
                }catch (\Exception $exception)
                {
                    return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);

                }
            }

            /**
             * @api {post} /feed/posts/<post_id>/unlike Unlike Feed-Post
             * @apiGroup Feed
             * @apiHeader {String} Authorization Authorization token
             * @apiHeaderExample {json} Header-Example:
             *     {
             *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
             *     }
             * @apiParam {Number} post_type_id Feed Post type
             * @apiParam {Number} data_id ID of what user is sharing, battle/trounament etc
             * @apiParamExample {json} Input
             *    {
             *      "post_id": 12,
             *    }
             * @apiSuccess {Boolean} error Error flag 
             * @apiSuccess {String} message Error message
             * @apiSuccessExample {json} Success
             *    HTTP/1.1 200 OK
             *    {
             *      "error": "false",
             *      "message": "Unliked",
             *    }
             * @apiErrorExample {json} Error response
             *    HTTP/1.1 200 OK
             *      {
             *          "error": "true",
             *          "message": "Invalid request"
             *      }
             * @apiVersion 1.0.0
             */
            public function postUnlike($postId)
            {
                $postId = (int) $postId;

                if ($postId) {
                    PostLikes::where('post_id', $postId)->where('user_id', \Auth::user()->id)->delete();

                    return response()->json(['error' => 'false', 'message' => 'Unliked']);
                }
            }

            /**
             * @api {get} /feed/posts/<post_id>/comments Get comments of feed-post
             * @apiGroup Feed
             * @apiHeader {String} Authorization Authorization token
             * @apiHeaderExample {json} Header-Example:
             *     {
             *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
             *     }
             * @apiParam {Number} post_id Feed-Post ID
             * @apiParamExample {json} Input
             *    {
             *      "post_id": 12,
             *    }
             * @apiSuccess {Boolean} error Error flag 
             * @apiSuccess {String} message Error message
             * @apiSuccessExample {json} Success
             *    HTTP/1.1 200 OK
             *    {
             *      "error": "false",
             *      "message": "",
             *      "data": [
             *           {
             *               "id": 6,
             *               "post_id": 2,
             *               "text": "Perfect..",
             *               "created_at": 1512566369,
             *               "user": {
             *                   "id": 25,
             *                   "first_name": "Rakesh",
             *                   "last_name": "Kumar",
             *                   "photo_url": null,
             *                   "user_following": false,
             *                   "user_follower": false,
             *                   "points": 809
             *               }
             *           },
             *           {
             *               "id": 5,
             *               "post_id": 2,
             *               "text": "Good one!",
             *               "created_at": 1512596625,
             *               "user": {
             *                   "id": 23,
             *                   "first_name": "Abhishek",
             *                   "last_name": "Nigam",
             *                   "photo_url": null,
             *                   "user_following": false,
             *                   "user_follower": false,
             *                   "points": 0
             *               }
             *           },
             *           {
             *               "id": 4,
             *               "post_id": 2,
             *               "text": "Great!",
             *               "created_at": 1512597885,
             *               "user": {
             *                   "id": 22,
             *                   "first_name": "Wes",
             *                   "last_name": "E",
             *                   "photo_url": null,
             *                   "user_following": false,
             *                   "user_follower": false,
             *                   "points": 0
             *               }
             *           },
             *           {
             *               "id": 3,
             *               "post_id": 2,
             *               "text": "Hey nice one!",
             *               "created_at": 1512591448,
             *               "user": {
             *                   "id": 20,
             *                   "first_name": "da",
             *                   "last_name": "cheng",
             *                   "photo_url": null,
             *                   "user_following": true,
             *                   "user_follower": true,
             *                   "points": 518
             *               }
             *           },
             *           {
             *               "id": 2,
             *               "post_id": 2,
             *               "text": "Yeah! Thanks",
             *               "created_at": 1512590440,
             *               "user": {
             *                   "id": 7,
             *                   "first_name": "Qiang",
             *                   "last_name": "Hu",
             *                   "photo_url": null,
             *                   "user_following": false,
             *                   "user_follower": false,
             *                   "points": 2308
             *               }
             *           },
             *           {
             *               "id": 1,
             *               "post_id": 2,
             *               "text": "Wow Congratulations!",
             *               "created_at": 1512590444,
             *               "user": {
             *                   "id": 1,
             *                   "first_name": "Nawaz",
             *                   "last_name": "Me",
             *                   "photo_url": null,
             *                   "user_following": true,
             *                   "user_follower": true,
             *                   "points": 80
             *               }
             *           }
             *       ]
             *    }
             * @apiErrorExample {json} Error response
             *    HTTP/1.1 200 OK
             *      {
             *          "error": "true",
             *          "message": "Invalid request"
             *      }
             * @apiVersion 1.0.0
             */
            public function getComments(Request $request, $postId)
            {
                $comments = [];

                $_comments = PostComments::where('post_id', (int) $postId)->orderBy('created_at', 'desc')->get();

                foreach ($_comments as $comment) {
                    $_comment = $comment->toArray();
                    unset($_comment['user_id']);
                    $_comment['user'] = \App\User::get($comment->user_id);

                    $comments[] = $_comment;
                }

                return response()->json(['error' => 'false', 'message' => '', 'data' => $comments]);
            }

            /**
             * @api {post} /feed/posts/<post_id>/comment Add comment to feed-post
             * @apiGroup Feed
             * @apiHeader {String} Authorization Authorization token
             * @apiHeaderExample {json} Header-Example:
             *     {
             *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
             *     }
             * @apiParam {Number} post_id Feed-Post ID
             * @apiParam {String} text Comment text
             * @apiParamExample {json} Input
             *    {
             *      "post_id": 12,
             *      "text": "This is feed-post comment",
             *    }
             * @apiSuccess {Boolean} error Error flag 
             * @apiSuccess {String} message Error message
             * @apiSuccessExample {json} Success
             *    HTTP/1.1 200 OK
             *    {
             *      "error": "false",
             *      "message": "Comment added",
             *    }
             * @apiErrorExample {json} Error response
             *    HTTP/1.1 200 OK
             *      {
             *          "error": "true",
             *          "message": "Invalid request"
             *      }
             * @apiVersion 1.0.0
             */
            public function postComment(Request $request, $postId)
            {
                $postId = (int) $postId;
                $post = Posts::find($postId);

                if ($post) {
                    PostComments::create([
                        'user_id' => \Auth::user()->id,
                        'post_id' => $postId,
                        'text' => $request->get('text'),
                    ]);

                    // Generates new notification for user
                    if ($post->user_id != \Auth::user()->id) {
                        \App\UserNotifications::generate(\App\UserNotifications::FEED_POST_COMMENT, $post->user_id, \Auth::user()->id, $postId);
                    }
                    return response()->json(['error' => 'false', 'message' => 'Comment added']);
                }else{
                    return response()->json(['error' => 'true', 'message' => 'Post not found']);

                }

            }

        }
        