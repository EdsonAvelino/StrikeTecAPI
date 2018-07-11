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

// Check for app update
$app->post('/check_update', 'AppController@checkForUpdate');

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
$app->get('/countries[/{phase}]', 'WorldController@getCountries');
$app->get('/states_by_country/{countryId}', 'WorldController@getStatesByCountry');
$app->get('/cities_by_state/{stateId}', 'WorldController@getCitiesByState');

// Subscription plans
// $app->get('/subscriptions', 'SubscriptionController@getSubscriptionList');

// Get FAQs
$app->get('/faqs', 'UserController@getFaqs');

// Get all available tags
$app->get('/tags', 'VideoController@getTags');

// Get list of trainers
$app->get('/trainers', 'VideoController@getTrainers');

// Rest of all APIs are secured with access-token
// User APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Update user's profile data
    $app->post('/users', 'UserController@update');

    // Know or update user's subscription
    $app->post('/users/subscription', 'UserController@postUserSubscription');
    
    // Update user's sensor data
    $app->post('/users/sensors', 'UserController@updateSensors');

    // Update user's preferences
    $app->post('/users/preferences', 'UserController@updatePreferences');

    // Search users
    $app->get('/users/search', 'UserController@searchUsers');

    // Get user's game score
    $app->get('/users/score', 'UserController@getUsersGameScores');

    // Get user's progress
    $app->get('/users/progress', 'UserController@getUsersProgress');

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
    // Save training (sensor) data
    $app->post('/user/training/data', 'TrainingController@storeData');

    // Get rounds by Training-Type
    $app->get('/user/training/sessions/rounds_by_training', 'TrainingController@getSessionsRoundsByTrainingType');

    // Get particular session
    $app->get('/user/training/sessions/for_comparison', 'TrainingController@getSessionForComparison');

    // Training sessions list
    $app->get('/user/training/sessions', 'TrainingController@getSessions');

    // Get particular session
    $app->get('/user/training/sessions/{sessionId}', 'TrainingController@getSession');

    // Save Training sessions
    $app->post('/user/training/sessions', 'TrainingController@storeSessions');

    // Archive Traning session
    $app->patch('/user/training/sessions/{sessionId}/archive', 'TrainingController@archiveSession');

    // Get round and its punches
    $app->get('/user/training/sessions/rounds/{round_id}', 'TrainingController@getSessionsRound');

    // Save Training sessions' rounds data to db
    $app->post('/user/training/sessions/rounds', 'TrainingController@storeSessionsRounds');

    // Save Training sessions' rounds' punches data to db
    $app->post('/user/training/sessions/rounds/punches', 'TrainingController@storeSessionsRoundsPunches');

    // Get Tips data
    $app->get('/tips', 'TrainingController@tips');

    // Get Achievement List
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

    // Get Video's Tags
    $app->get('/videos/tags', 'VideoController@getVideoTags');

    // Get Categories
    $app->get('/videos/category', 'VideoController@getVideoCategories');
});

// Leaderboard APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Get list of leaderboard data
    $app->get('/leaderboard', 'LeaderboardController@getList');

    // Trending data
    $app->get('/trending', 'LeaderboardController@getTrendingList');

    // Game leaderboard data
    $app->get('/leaderboard/game', 'LeaderboardController@getGameLeaderboardData');
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

// Get list of comobos
$app->get('/battles/combos', 'BattleController@getCombos');

// Get list of comobo-sets
$app->get('/battles/combo_sets', 'BattleController@getComboSets');

// Get list of workouts
$app->get('/battles/workouts', 'BattleController@getWorkouts');

// Not in use for now so commenting (17032018)
// Upload combo audio
// $app->post('/combos/audio', 'BattleController@saveAudio');

// list of combos with audios
// $app->get('/battles/combos/audio', 'BattleController@getCombosAudio');

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

    // Delete a message
    $app->delete('/chat/{messageId}', 'ChatController@deleteMessage');
});

// Tournaments APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Get all new / joined / finished tournaments
    $app->get('/tournaments/all', 'TournamentController@getAllEventsList');

    // Get all new tournaments user didn't join
    $app->get('/tournaments', 'TournamentController@getEventsList');

    // Tournament activity details
    $app->get('/tournaments/{eventActivityId}', 'TournamentController@getEventActivityDetails');

    // Tournament activity leaderboard
    $app->get('/tournaments/{eventActivityId}/leaderboard', 'TournamentController@getEventActivityLeaderboard');
      
    // User Join the tournament
    $app->post('/user/tournaments/join', 'TournamentController@userJoinTournament');
    
    // Get all tournaments that user joined
    $app->get('/user/tournaments', 'TournamentController@getUserJoinedTournaments');

    // Get all finished tournaments that user joined
    $app->get('/user/tournaments/finished', 'TournamentController@getUserFinishedTournaments');
    
    // Get user's tournament connections who haven not joined yet
    $app->get('/user/tournaments/{eventActivityId}/connections', 'TournamentController@getUserTournamentConnections');

    // Invite connection for tournament 
    $app->post('/user/tournaments/invite', 'TournamentController@getUserTournamentInvite');
});

// Guidance APIs
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Guidance home screen
    $app->get('/guidance/home', 'GuidanceController@home');
    
    // Getting list of combos/set-routines/workouts (plans)
    $app->get('/guidance/plans/{type_id}', 'GuidanceController@getPlans');
    $app->get('/guidance/plans/{typeId}/{planId}', 'GuidanceController@getPlanDetail');

    $app->get('/guidance/essentials', 'GuidanceController@getEssentialsVideos');
    $app->get('/guidance/essentials/{id}', 'GuidanceController@getEssentialsVideoDetail');

    // Rating
    $app->post('/guidance/rate', 'GuidanceController@postRating');
});

// In-App Purchase APIs [these APIs don't need authorization]
// Get IAP get list of products
$app->get('/iap/products/{platform}', 'IapController@getProducts');

// Store In-App Purchase receipts
$app->post('/iap/receipt', 'IapController@storeReceipt');

// Fan App APIs routes
// These API does not need auth

// Get list of Fan APP Companies
$app->get('/fan/companies', 'CompanyController@getCompanyList');

// FAN App admin register
$app->post('/fan/user/register', 'FanUserController@registerFanAdmin');

// FAN App admin login
$app->post('/fan/auth/login', 'FanUserController@authenticate');

// FAN App APIs
$app->group(['middleware' => 'auth:fan'], function() use ($app) {
    // Get my events list
    $app->get('/fan/events', 'EventController@getMyEventsList');

    // Get list of all of the events
    $app->get('/fan/events/all', 'EventController@getAllEventsList');

    // New Event
    $app->post('/fan/events', 'EventController@postEvent');

    // Update an Event
    $app->post('/fan/events/{eventId}', 'EventController@postUpdateEvent');
    
    // Delete an event
    $app->delete('/fan/events/{eventId}', 'EventController@deleteEvent');
    
    // Get fan activity types
    $app->get('/fan/activities', 'EventController@getEventActivityTypes');

    // Get user's event list by country id for fan APP 
    $app->get('/fan/users', 'FanUserController@getUsersList');
    
    // Register user to db
    $app->post('/fan/users', 'FanUserController@postUserToDb');
    
    // Get list of event activities with users
    $app->get('/fan/events/{eventId}/activities', 'EventController@getEventActivities');

    // Create activity into event
    $app->post('/fan/events/{eventId}/activities', 'EventController@postAddEventActivity');

    // Remove activity
    $app->delete('/fan/events/{eventId}/activities', 'EventController@deleteEventActivity');

    // List of locations
    $app->get('/fan/locations', 'LocationController@getLocationsList');

    // Change password
    $app->post('/fan/user/change_password', 'FanUserController@setFanUserPassword');
    
    // Get Event Activity participants
    $app->get('/fan/events/activities/{eventActivityId}/users', 'EventController@getEventActivityParticipants');

    // Add user to Event Activity
    $app->post('/fan/events/activities/users', 'EventController@postUsersToEventActivity');
    
    // Remove user from Event Activity
    $app->delete('/fan/events/activities/users', 'EventController@deleteUsersFromEventActivity');

    // Authorize user for Event Activity
    $app->post('/fan/events/activities/users/authorize', 'EventController@authorizeUserForEventActivity');

    // Store event activity sessoins' punches data
    $app->post('/fan/events/activities/sessions', 'EventController@storeEventSessions');
    
    // Get event activity leaderboard 
    $app->get('/fan/events/activities/{eventActivityId}/leaderboard', 'EventController@getLeaderboardByEventActivity');
     
    // Update activity status
    $app->post('/fan/events/activities/status', 'EventController@postStatusUpdateEventActivity');
});
