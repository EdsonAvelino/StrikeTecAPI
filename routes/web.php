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
$app->get('/countries', 'WorldController@getCountries');
$app->get('/states_by_country/{countryId}', 'WorldController@getStatesByCountry');
$app->get('/cities_by_state/{stateId}', 'WorldController@getCitiesByState');

//Subscription plans
$app->get('/subscriptions', 'SubscriptionController@getSubscriptionList');

// Get FAQs
$app->get('/faqs', 'UserController@getFaqs');

// Rest of all APIs are secured with access-token
// User APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Update user's profile data
    $app->post('/users', 'UserController@update');

    // Update user's preferences
    $app->post('/users/preferences', 'UserController@updatePreferences');

    // Get user's information
    $app->get('/users/{userId}', 'UserController@getUser');

    // Get user's information
    $app->post('/users/change_password', 'UserController@setUserPassword');

    // User's social connectivity
    $app->get('/user/follow/{userId}', 'UserController@follow');
    $app->get('/user/unfollow/{userId}', 'UserController@unfollow');

    $app->get('/user/followers', 'UserController@getFollowers');
    $app->get('/user/{userId}/followers', 'UserController@getFollowersOfUser');

    $app->get('/user/following', 'UserController@getFollowing');
    $app->get('/user/{userId}/following', 'UserController@getFollowingOfUser');

    $app->get('/user/connections', 'UserController@getConnections');
});

// Training APIs
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

// Video APIs
// TEMP: Sync list of videos from storage/videos dir to db
// $app->get('/videos/sync', 'VideoController@syncVideos');

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

// Leaderboard APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Get list of leaderboard data
    $app->get('/leaderboard', 'LeaderboardController@getList');

    // Explore data
    $app->get('/explore', 'LeaderboardController@getExploreList');
});

// Push notifications APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Save customer token for push notifications
    $app->post('/user/app_token', 'PushController@storeAppToken');
});

// Push notification tests for ios/android
$app->post('/push/test', 'PushController@testPush');
$app->post('/push/test/apns', 'PushController@testPushAPNs');

// Battle APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {

    // Get battle Request
    $app->get('/battles/received', 'BattleController@getReceivedRequests');

    // Get my battles
    $app->get('/battles/my_battles', 'BattleController@getMyBattles');

    // Get finished battles
    $app->get('/battles/finished', 'BattleController@getFinishedBattles');

    // Get all battles
    $app->get('/battles/all', 'BattleController@getAllBattles');

    // Send battle invite to another user    
    $app->post('/battles', 'BattleController@postBattleWithInvite');

    // Accept/Decline battle invite (Opponent user)
    $app->post('/battles/accept_decline', 'BattleController@updateBattleInvite');

    // Resent battle invite
    $app->get('/battles/resend/{battleId}', 'BattleController@resendBattleInvite');

    // Cancel battle
    $app->get('/battles/cancel/{battleId}', 'BattleController@cancelBattle');

    // Get list of comobos
    $app->get('/battles/combos', 'BattleController@getCombos');

    // upload audio
    $app->post('/combos/audio', 'BattleController@saveAudio');
    
    //lis of combos with audios
    $app->get('/battles/combos/audio', 'BattleController@getCombosAudio');

    // Get list of comobo-sets
    $app->get('/battles/combo_sets', 'BattleController@getComboSets');

    // Get list of workouts
    $app->get('/battles/workouts', 'BattleController@getWorkouts');

    // Get details of battle(challenge)
    $app->get('/battles/{battleId}', 'BattleController@getBattle');

    //push notifications settings
    $app->post('/notification/settings', 'SettingController@updateSettings');
    $app->get('/notification/settings', 'SettingController@getSettings');
});

// Goals APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {

    // Get list of activities
    $app->get('/activities', 'ActivityController@getActivityList');

    // Get list of activity type
    $app->get('/activity/types[/{activity_id}]', 'ActivityController@getActivityTypeList');

    // Set new goal
    $app->post('/goal/add', 'GoalController@newGoal');
});

// Feed APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Get list of feed-posts
    $app->get('/feed/posts', 'FeedController@getPosts');

    // Add new feed-post
    $app->post('/feed/posts', 'FeedController@addPost');

    // Like/Unlike feed-post
    $app->post('/feed/posts/{postId}/like', 'FeedController@postLike');
    $app->post('/feed/posts/{postId}/unlike', 'FeedController@postUnlike');

    // Get comments of feed-post
    // $app->get('/feed/posts/{postId}/comment', 'FeedController@getComments');
    // Post comment on feed-post
    $app->post('/feed/posts/{postId}/comment', 'FeedController@postComment');
});

//contact us(write us)
$app->post('/writeus', 'WriteusController@writeUs');


//Chat APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {

    // Send message
    $app->post('/chat/send', 'ChatController@sendMessage');

    // read message
    $app->post('/chat/read', 'ChatController@ReadMessage');

    // chat History
    $app->get('/chat/history', 'ChatController@chatHistory');

    // all chats
    $app->get('/chat', 'ChatController@chats');
});
