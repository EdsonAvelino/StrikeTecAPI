<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Posts;
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
        
        $posts = Posts::offset($offset)->limit($limit)->get();

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
     * @apiParam {Number} post_type_id Feed Post type
     * @apiParam {Number} data_id ID of what user is sharing, battle/trounament etc
     * @apiParamExample {json} Input
     *    {
     *      "post_type_id": 1,
     *      "data_id": 1
     *    }
     * @apiSuccess {Boolean} error Error flag 
     * @apiSuccess {String} message Error message
     * @apiSuccessExample {json} Success
     *    HTTP/1.1 200 OK
     *    {
     *      "error": "false",
     *      "message": "User invited for battle successfully",
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
            'likes' => 0,
        ]);

        return response()->json(['error' => 'false', 'message' => 'Successfully shared to feed']);
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

        if ($postId) {
            $post = Posts::find($postId);
            
            $post->likes = $post->likes + 1;
            $post->save();

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

        if ($postId) {
            $post = Posts::find($postId);
            
            $post->likes = ($post->likes > 0) ? ($post->likes - 1) : 0;
            $post->save();

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
