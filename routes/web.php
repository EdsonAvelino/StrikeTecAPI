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

// get tags
$app->get('/tags', 'BattleController@getTags');

// Rest of all APIs are secured with access-token
// User APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Update user's profile data
    $app->post('/users', 'UserController@update');

    // Update user's preferences
    $app->post('/users/preferences', 'UserController@updatePreferences');

    // Get user's information
    $app->get('/users/{userId}', 'UserController@getUser');

    // Change password
    $app->post('/users/change_password', 'UserController@setUserPassword');

    // User's social connectivity
    // Get user's connections
    $app->get('/user/connections/{userId}', 'UserController@getConnections');

    // Get suggestions to follow
    $app->get('/user/follow/suggestions', 'UserController@getFollowSuggestions');

    $app->get('/user/follow/{userId}', 'UserController@follow');
    $app->get('/user/unfollow/{userId}', 'UserController@unfollow');

    $app->get('/user/followers', 'UserController@getFollowers');
    $app->get('/user/{userId}/followers', 'UserController@getFollowersOfUser');

    $app->get('/user/following', 'UserController@getFollowing');
    $app->get('/user/{userId}/following', 'UserController@getFollowingOfUser');

    $app->get('/user/unread_counts', 'UserController@getUnreadCounts');

    $app->get('/user/notifications', 'UserController@getNotifications');
    $app->get('/user/notifications/read/{notificationId}', 'UserController@readNotifications');
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

    // Save Training sessions' rounds data to db
    $app->post('/user/training/sessions/rounds', 'TrainingController@storeSessionsRounds');

    // Save Training sessions' rounds' punches data to db
    $app->post('/user/training/sessions/rounds/punches', 'TrainingController@storeSessionsRoundsPunches');

    //get Tips data
    $app->get('/tips', 'TrainingController@tips');

    //get Achievement List
    $app->get('/achievements', 'AchievementController@getAchievementList');
});

// Video APIs
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

    // Get tags
    $app->get('/videos/tags', 'VideoController@getVideoTags');

    // Get tags
    $app->get('/videos/category', 'VideoController@getVideoCat');
});

// Leaderboard APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Get list of leaderboard data
    $app->get('/leaderboard', 'LeaderboardController@getList');

    // Trending data
    $app->get('/trending', 'LeaderboardController@getTrendingList');
});

// Push notifications APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Save customer token for push notifications
    $app->post('/user/app_token', 'PushController@storeAppToken');
});

// Push notifications settings APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    $app->post('/notification/settings', 'SettingController@updateSettings');
    $app->get('/notification/settings', 'SettingController@getSettings');
});

// Battle APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {

    // Get battle Request
    $app->get('/battles/received', 'BattleController@getReceivedRequests');

    // Get my battles
    $app->get('/battles/my_battles', 'BattleController@getMyBattles');

    // Get finished battles
    $app->get('/battles/finished', 'BattleController@getAllFinishedBattles');

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

    //list of combos with audios
    $app->get('/battles/combos/audio', 'BattleController@getCombosAudio');

    // Get list of comobo-sets
    $app->get('/battles/combo_sets', 'BattleController@getComboSets');

    // Get list of workouts
    $app->get('/battles/workouts', 'BattleController@getWorkouts');

    // Get details of battle(challenge)
    $app->get('/battles/{battleId}', 'BattleController@getBattle');

    // Get battles of user 
    $app->get('/battles/user/finished', 'BattleController@getUsersFinishedBattles');
});

// Goals APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {

    // Get list of activities
    $app->get('/activities', 'ActivityController@getActivityList');

    // Get list of activity type
    $app->get('/activity/types[/{activity_id}]', 'ActivityController@getActivityTypeList');

    // Set new goal
    $app->post('/goal/add', 'GoalController@newGoal');

    // edit goal
    $app->post('/goal/edit', 'GoalController@updateGoal');

    // follow goal
    $app->post('/goal/follow', 'GoalController@followGoal');

    // delete goal
    $app->delete('/goal/{goal_id}', 'GoalController@deleteGoal');

    // GET list of goal
    $app->get('/goals', 'GoalController@getGoalList');

    // Calculate goal data
    $app->get('/goal/info', 'GoalController@goalInfo');

    // Calculate goal data
    $app->get('/goal', 'GoalController@goal');
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
    $app->get('/feed/posts/{postId}/comments', 'FeedController@getComments');

    // Post comment on feed-post
    $app->post('/feed/posts/{postId}/comment', 'FeedController@postComment');
});

// This API does not need auth
// Contact Us(write us)
$app->post('/writeus', 'WriteusController@writeUs');

// Chat APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {

    // Send message
    $app->post('/chat/send', 'ChatController@sendMessage');

    // Read message
    $app->post('/chat/read', 'ChatController@ReadMessage');

    // Chat History (get all messages of particular chat )
    $app->get('/chat/history', 'ChatController@chatHistory');

    // Get all chats
    $app->get('/chat', 'ChatController@chats');
});

// Fan App APIs routes
// This API does not need auth  
/* ~~~~~~~~~~~~~~~~~~~~~ FAN APP API ROUTES ~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
//get user list for fan APP
$app->get('/fan/companies', 'CompanyController@getCompanyList');

// register user for fan APP API
$app->post('fan/user/register', 'FanUserController@registerFan');

// login for fan APP user
$app->post('/fan/login/auth', 'FanUserController@authenticate');

//fan app authentication
$app->group(['middleware' => 'auth:fanuser'], function() use ($app) {

    //add event for fan API
    $app->post('/fan/event', 'EventController@addEvent');
    
    //remove event for fan APP API'
    $app->post('/fan/event/remove', 'EventController@eventRemove');
    
    //get users list by country id for fan APP 
    $app->get('/fan/users/events', 'EventController@userEvents');
    
    //get all events list for fan APP APIs
    $app->get('/fan/all/events', 'EventController@allEventsUsersList');
    
    //get my events list for fan APP API
    $app->get('/fan/my/events', 'EventController@myEventsUsersList');
    
    //add event for fan API
    $app->post('/fan/event/users/add', 'EventUserController@usersAddEvent');
    
    //add event for fan API
    $app->get('/fan/locations', 'LocationController@getLocationList');
    
    //register fan userto add db
    $app->post('/fan/event/register/user', 'EventUserController@addUserToDb');

    // register activity into event for Fan API
    $app->post('/fan/event/activity/add', 'EventFanActivityController@activityAddEvent');
    
    // get fan activity list
    $app->get('/fan/activities', 'FanActivityController@getActivityList');

    // Change password
    $app->post('/fan/user/change_password', 'FanUserController@setFanUserPassword');
    
    //get users list and activity list by eventID
    $app->get('/fan/event/users/activities/{event_id}', 'EventController@getUsersActivitiesInfoByEvent');
    
    //remove activity
    $app->post('/fan/activity/remove', 'EventFanActivityController@activityRemove');
    
    //remove users from event
    $app->post('/fan/event/users/remove', 'EventUserController@eventUsersRemove');
    
    //get users list by user name#searching
    $app->post('/fan/users/search', 'UserController@getUsersListBySerch');
    
    //get active event details by logged user
    $app->get('/fan/events/logged/user', 'EventController@getuserActiveEventsList');

    // Change password
    $app->post('/fan/user/change_password', 'FanUserController@setFanUserPassword');
    
      //get leaderboard details by event id and activity id
    $app->get('/fan/event/leaderboard', 'EventTrainingController@getLeaderboardByEventActivity');

    // register event training sessoins' rounds' punches data to db
    $app->post('/fan/event/training/sessions', 'EventTrainingController@storeEventSessions');
    
    //get leaderboard detials for user
    $app->get('/fan/event/leaderboard', 'EventTrainingController@getLeaderboardByEventActivity');
     
    //update activity status
    $app->post('/fan/event/activity/status', 'EventController@statusChangeActivity');
     
    //remove participant
    $app->delete('/fan/event/participant/remove', 'EventTrainingController@eventParticipantsRemove');
    
    // get pending for approval users list by event id
    $app->get('/fan/event/users/pending/{event_id}', 'EventController@eventPendingUsersList');
    
     // update status of user approval or not by event id
    $app->post('/fan/event/users/status', 'EventController@eventUsersStatus');
});

// Tournaments APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Get all new tournament apis user didn't join
    $app->get('/tournaments', 'TournamentController@getEventsList');
      
    // User Join the tournament
    $app->post('/user/tournaments/join', 'TournamentController@userJoinTournament');
    
    // Get all tournaments that user joined
    $app->get('/user/tournaments', 'TournamentController@getUserJoinedTournaments');

    // Get all finished tournaments that user joined
    $app->get('/user/tournaments/finished', 'TournamentController@getUserFinishedTournaments');
    
    // Get user's tournament connections who haven not joined yet
    $app->get('/user/tournaments/{eventActivityId}/connections', 'TournamentController@getUserTournamentConnections');
});
