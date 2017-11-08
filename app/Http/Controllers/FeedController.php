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
     *      "data": ""
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
            $q->select('id','first_name', 'last_name', 'photo_url', 'gender');
        }])
        ->whereRaw('user_id IN (SELECT follow_user_id as "user_id" FROM user_connections WHERE user_id = ?)', [\Auth::user()->id])
        ->orWhere('user_id', \Auth::user()->id)
        ->withCount('likes')->withCount('comments')
        ->offset($offset)->limit($limit)->orderBy('created_at', 'desc')->get();

        // dd(\DB::getQueryLog());

        foreach ($_posts as $post) {
            $_post = $post->toArray();

            $user1FullName = $post->user->first_name.' '.$post->user->last_name;
            
            $user2FullName = null;

            switch ($post->post_type_id) {
                case 1:
                    $user2FullName = $post->data->opponentUser->first_name.' '.$post->data->opponentUser->last_name;
                    break;

                case 5:
                    $user = \App\User::find($post->data_id);
                    $user2FullName = $user->first_name.' '.$user->last_name;
            }

            $userTemplate = (strtolower($post->user->gender) == 'female') ? 'her' : 'his';

            $_post['title'] = str_replace(['_USER1_', '_TEMPLATE_', '_USER2_'],
                [$user1FullName, $userTemplate, $user2FullName], $post->title);

            $userLikes = PostLikes::where('post_id', $post->id)->where('user_id', \Auth::user()->id)->exists();
            
            $_post['user_likes'] = (bool) $userLikes;
            $posts[] = $_post;
        }

        return response()->json(['error' => 'false', 'message' => '', 'data' => $posts]);
    }

    /**
     * @api {post} /feed/posts Add new Feed-Post
     * @apiGroup Feed
     * @apiHeader {String} Content-Type application/x-www-form-urlencoded
     * @apiHeader {String} Authorization Authorization token
     * @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3Mi....LBR173t-aE9lURmUP7_Y4YB1zSIV1_AN7kpGoXzfaXM"
     *     }
     * @apiParam {Number} post_type_id Feed-Post type e.g. 1=Battle, 2=Training, 3=Tournament, 4=Game, 5=Following
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
        $post = Posts::create([
            'user_id' => \Auth::user()->id,
            'post_type_id' => (int) $request->get('post_type_id'),
            'data_id' => (int) $request->get('data_id'),
            'text' => $request->get('text')
        ]);

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
        $postId = (int) $postId;

        if ( $postId &&
            !(PostLikes::where('post_id', $postId)->where('user_id', \Auth::user()->id)->exists())
        ) {

            PostLikes::create([
                'post_id' => $postId,
                'user_id' => \Auth::user()->id,
            ]);

            return response()->json(['error' => 'false', 'message' => 'Liked']);
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

        if ( $postId ) {
            PostLikes::where('post_id', $postId)->where('user_id', \Auth::user()->id)->delete();

            return response()->json(['error' => 'false', 'message' => 'Unliked']);
        }
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
    public function postComment(Request $request)
    {
        PostComments::create([
            'user_id' => \Auth::user()->id,
            'post_id' => (int) $request->get('post_id'),
            'text' => $request->get('text'),
        ]);

        return response()->json(['error' => 'false', 'message' => 'Comment added']);
    }
}
