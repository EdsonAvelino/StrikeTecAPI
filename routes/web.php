<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return response(['error' => 'Not found'], 404);
});

// Login
$app->post('/auth/login', 'AuthController@authenticate');
$app->post('/auth/facebook', 'AuthController@authenticateFacebook');

// User Signup
$app->post('/user/register', 'UserController@register');
$app->post('/user/register/facebook', 'UserController@registerFacebook');

// Password Reset
$app->post('/password', 'PasswordController@postEmail');
$app->post('/password/verify_code', 'PasswordController@postVerifyCode');
$app->post('/password/reset', 'PasswordController@postReset');

// Countries / States / Cities
$app->get('/countries', function () use ($app) {
    $countries = \App\Countries::get();

    return response()->json(['error' => 'false', 'message' => '', 'data' => $countries->toArray()]);
});

$app->get('/states_by_country/{countryId}', function ($countryId) use ($app) {
    $states = \App\States::where('country_id', $countryId)->get();

    return response()->json(['error' => 'false', 'message' => '', 'data' => $states->toArray()]);
});

$app->get('/cities_by_state/{stateId}', function ($stateId) use ($app) {
    $cities = \App\Cities::where('state_id', $stateId)->get();

    return response()->json(['error' => 'false', 'message' => '', 'data' => $cities->toArray()]);
});

// Rest of all APIs are secured with access-token
// User APIs//
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Update user's profile data
    $app->post('/users', 'UserController@update');

    // Get user's information
    $app->get('/users/{userId}', 'UserController@getUser');

    // Get user's information
    $app->post('/user/preferences', 'UserController@updatePreferences');

    // User's social connectivity
    $app->get('/user/follow/{userId}', 'UserController@follow');
    $app->get('/user/unfollow/{userId}', 'UserController@unfollow');
    $app->get('/user/followers', 'UserController@getFollowers');
    $app->get('/user/following', 'UserController@getFollowing');
});

// Training APIs//
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Get rounds by Training-Type
    $app->get('/user/training/sessions/rounds_by_training', 'TrainingController@getSessionsRoundsByTrainingType');
    
    // Training sessions list
    $app->get('/user/training/sessions', 'TrainingController@getSessions');

    // Get particular session
    $app->get('/user/training/sessions/{sessionId}', 'TrainingController@getSession');

    // Save Training sessions data to db
    $app->post('/user/training/sessions', 'TrainingController@storeSessions');

    // Get round and its punches
    $app->get('/user/training/sessions/rounds/{round_id}', 'TrainingController@getSessionsRound');

    // Save Training sessoins' rounds data to db
    $app->post('/user/training/sessions/rounds', 'TrainingController@storeSessionsRounds');

    // Save Training sessoins' rounds' punches data to db
    $app->post('/user/training/sessions/rounds/punches', 'TrainingController@storeSessionsRoundsPunches');
});

// Video APIs//

// Sync list of videos from storage/videos dir to db
$app->get('/videos/sync', 'VideoController@syncVideos');

$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Get list of videos available on server
    $app->get('/videos', 'VideoController@getVideos');

    // Get list of videos available on server
    $app->get('/videos/search', 'VideoController@searchVideos');

    // Update video
    $app->post('/videos/add_view/{videoId}', 'VideoController@addViewCount');

    // Set video favourite for user
    $app->post('/videos/favourite/{videoId}', 'VideoController@setVideoFav');

    // Set video unfavourite for user
    $app->post('/videos/unfavourite/{videoId}', 'VideoController@setVideoUnFav');

    // Get user's favourited videos
    $app->get('/user/fav_videos', 'VideoController@getUserFavVideos');
});