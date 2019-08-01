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
$router->group(['namespace' => '\Rap2hpoutre\LaravelLogViewer'], function() use ($app) {
    $router->get('/logs', 'LogViewerController@index');
});
$router->get('/', function () use ($app) {
    return response(['error' => 'Not found'], 404);
});
//$router->group(['prefix' => 'api/v1'], function () use ($app) {
//$router->group(['prefix' => 'v1'], function () use ($app) {
        // Check for app update
        $router->post('/check_update', 'AppController@checkForUpdate');
        // Check for app update
        $router->post('/check_update', 'AppController@checkForUpdate');
        // Login
        $router->post('/auth/login', 'AuthController@authenticate');
        $router->post('/auth/facebook', 'AuthController@authenticateFacebook');
        // User Signup
        $router->post('/user/register', 'UserController@register');
        $router->post('/user/register/facebook', 'UserController@registerFacebook');
        // Password Reset
        $router->post('/password', 'PasswordController@postEmail');
        $router->post('/password/verify_code', 'PasswordController@postVerifyCode');
        $router->post('/password/reset', 'PasswordController@postReset');
        // Countries / States / Cities
        $router->get('/countries[/{phase}]', 'WorldController@getCountries');
        $router->get('/states_by_country/{countryId}', 'WorldController@getStatesByCountry');
        $router->get('/cities_by_state/{stateId}', 'WorldController@getCitiesByState');
        //Subscription plans
        //$router->get('/subscriptions', 'SubscriptionController@getSubscriptionList');
        // Get FAQs
        $router->get('/faqs', 'UserController@getFaqs');
        // Get all available tags
        //$router->get('/tags', 'VideoController@getTags');
        // Get list of trainers
        $router->get('/trainers', 'VideoController@getTrainers');
        // Rest of all APIs are secured with access-token
        // User APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Update user's profile data
            $router->post('/users', 'UserController@update');
            // Know or update user's subscription
            $router->post('/users/subscription', 'UserController@postUserSubscription');
            
            // Update user's sensor data
            $router->post('/users/sensors', 'UserController@updateSensors');
            // Update user's preferences
            $router->post('/users/preferences', 'UserController@updatePreferences');
            // Search users
            $router->get('/users/search', 'UserController@searchUsers');
            // Get user's game score
            $router->get('/users/score', 'UserController@getUsersGameScores');
            // Get user's progress
            $router->get('/users/progress', 'UserController@getUsersProgress');
            // Get user's information
            $router->get('/users/{userId}', 'UserController@getUser');
            // Change password
            $router->post('/users/change_password', 'UserController@setUserPassword');
            // User's social connectivity
            // Get user's connections
            $router->get('/user/connections/{userId}', 'UserController@getConnections');
            // Get suggestions to follow
            $router->get('/user/follow/suggestions', 'UserController@getFollowSuggestions');
            $router->get('/user/follow/{userId}', 'UserController@follow');
            $router->get('/user/unfollow/{userId}', 'UserController@unfollow');
            $router->get('/user/followers', 'UserController@getFollowers');
            $router->get('/user/{userId}/followers', 'UserController@getFollowersOfUser');
            $router->get('/user/following', 'UserController@getFollowing');
            $router->get('/user/{userId}/following', 'UserController@getFollowingOfUser');
            $router->get('/user/unread_counts', 'UserController@getUnreadCounts');
            //$router->get('/user/runSomethingInServer', 'UserController@runSomethingInServer');
            $router->get('/user/notifications', 'UserController@getNotifications');
            $router->get('/user/notifications/read/{notificationId}', 'UserController@readNotifications');
            $router->get('/user/notifications/read_all', 'UserController@readAllNotifications');
        });
        // Coach User APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Update client's profile data
            $router->post('/coach/clients', 'CoachUserController@addClient');
            // Search clients
            $router->get('/coach/clients', 'CoachUserController@getClientsList');
            // Get client's information
            $router->get('/coach/client/{userId}', 'UserController@getUser');
        });
        // Training APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            
            // Save training (sensor) data
            $router->post('/user/training/data', 'TrainingController@storeData');
            // Get rounds by Training-Type
            $router->get('/user/training/sessions/rounds_by_training', 'TrainingController@getSessionsRoundsByTrainingType');
            // Get particular session
            $router->get('/user/training/sessions/for_comparison', 'TrainingController@getSessionForComparison');
                // Training sessions list
                $router->get('/user/training/sessions', 'TrainingController@getSessions');
                // Get particular session
                $router->get('/user/training/sessions/{sessionId}', 'TrainingController@getSession');
                // Save Training sessions
                $router->post('/user/training/sessions', 'TrainingController@storeSessions');
                // Archive Traning session
                $router->patch('/user/training/sessions/{sessionId}/archive', 'TrainingController@archiveSession');
                // Get round and its punches
                $router->get('/user/training/sessions/rounds/{round_id}', 'TrainingController@getSessionsRound');
                // Save Training sessions' rounds data to db
                $router->post('/user/training/sessions/rounds', 'TrainingController@storeSessionsRounds');
                // Save Training sessions' rounds' punches data to db
                $router->post('/user/training/sessions/rounds/punches', 'TrainingController@storeSessionsRoundsPunches');
                //get Tips data
                $router->get('/tips', 'TrainingController@tips');
            // Get Tips data
            $router->get('/tips', 'TrainingController@tips');
            //$router->get('calculatebadges/{user_id}/{session_id}/{battle_id}','TrainingController@achievements');
            // Get Achievement List
            $router->get('/achievements', 'AchievementController@getAchievementList');
        });
        
        // Get list of videos available on server
        $router->get('/videos/search', 'VideoController@searchVideos');
        // Update video
        $router->post('/videos/add_view/{videoId}', 'VideoController@addViewCount');
        // Set video favourite for user
        $router->post('/videos/favourite/{videoId}', 'VideoController@setVideoFav');
        // Set video unfavourite for user
        $router->post('/videos/unfavourite/{videoId}', 'VideoController@setVideoUnFav');
        // Get user's favourited videos
        $router->get('/user/fav_videos', 'VideoController@getUserFavVideos');
        // Get Video's Tags
        $router->get('/videos/tags', 'VideoController@getVideoTags');
        // Get Categories
        //$router->get('/videos/category', 'VideoController@getVideoCategories');
        // Get list of videos available on server
        $router->get('/videos/filter', 'VideoController@videosFilter');
        // Get list of videos available on server
        $router->get('/videos/count', 'VideoController@videosCount');
        // Leaderboard APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Get list of leaderboard data
            $router->get('/leaderboard', 'LeaderboardController@getList');
            // Trending data
            $router->get('/trending', 'LeaderboardController@getTrendingList');
            // Game leaderboard data
            $router->get('/leaderboard/game', 'LeaderboardController@getGameLeaderboardData');
        });
        // Push notifications APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Save customer token for push notifications
            $router->post('/user/app_token', 'PushController@storeAppToken');
        });
        // Push notifications settings APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            $router->post('/notification/settings', 'SettingController@updateSettings');
            $router->get('/notification/settings', 'SettingController@getSettings');
        });
        // Get list of comobos
        $router->get('/battles/combos', 'BattleController@getCombos');
        // Get list of comobo-sets
        $router->get('/battles/combo_sets', 'BattleController@getComboSets');
        // Get list of workouts
        $router->get('/battles/workouts', 'BattleController@getWorkouts');
        // Not in use for now so commenting (17032018)
        // Upload combo audio
        // $router->post('/combos/audio', 'BattleController@saveAudio');
        // list of combos with audios
        // $router->get('/battles/combos/audio', 'BattleController@getCombosAudio');
        // Battle APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Get battle Request
            $router->get('/battles/received', 'BattleController@getReceivedRequests');
            // Get my battles
            $router->get('/battles/my_battles', 'BattleController@getMyBattles');
            // Get sent battles
            $router->get('/battles/sent', 'BattleController@getSentBattles');
            // Get finished battles
            $router->get('/battles/finished', 'BattleController@getAllFinishedBattles');
            // Get all battles
            $router->get('/battles/all', 'BattleController@getAllBattles');
            // Send battle invite to another user    
            $router->post('/battles', 'BattleController@postBattleWithInvite');
            // Accept/Decline battle invite (Opponent user)
            $router->post('/battles/accept_decline', 'BattleController@updateBattleInvite');
            // Resent battle invite
            $router->get('/battles/resend/{battleId}', 'BattleController@resendBattleInvite');
            // Cancel battle
            $router->get('/battles/cancel/{battleId}', 'BattleController@cancelBattle');
            // Get details of battle(challenge)
            $router->get('/battles/{battleId}', 'BattleController@getBattle');
            // Get battles of user 
            $router->get('/battles/user/finished', 'BattleController@getUsersFinishedBattles');
        });
        // Goals APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Get list of activities
            $router->get('/activities', 'ActivityController@getActivityList');
            // Get list of activity type
            $router->get('/activity/types[/{activity_id}]', 'ActivityController@getActivityTypeList');
            // Set new goal
            $router->post('/goal/add', 'GoalController@newGoal');
            // edit goal
            $router->post('/goal/edit', 'GoalController@updateGoal');
            // follow goal
            $router->post('/goal/follow', 'GoalController@followGoal');
            // delete goal
            $router->delete('/goal/{goal_id}', 'GoalController@deleteGoal');
            // GET list of goal
            $router->get('/goals', 'GoalController@getGoalList');
            // Calculate goal data
            $router->get('/goal/info', 'GoalController@goalInfo');
            // Calculate goal data
            $router->get('/goal', 'GoalController@goal');
        });
        // Feed APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Get list of feed-posts
            $router->get('/feed/posts', 'FeedController@getPosts');
            // Add new feed-post
            $router->post('/feed/posts', 'FeedController@addPost');
            // Like/Unlike feed-post
            $router->post('/feed/posts/{postId}/like', 'FeedController@postLike');
            $router->post('/feed/posts/{postId}/unlike', 'FeedController@postUnlike');
            // Get comments of feed-post
            $router->get('/feed/posts/{postId}/comments', 'FeedController@getComments');
            // Post comment on feed-post
            $router->post('/feed/posts/{postId}/comment', 'FeedController@postComment');
        });
        // Chat APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Send message
            $router->post('/chat/send', 'ChatController@sendMessage');
            // Read message
            $router->post('/chat/read', 'ChatController@ReadMessage');
            // Read message
            $router->post('/chat/edit', 'ChatController@chatEdit');
            // Get all chats
            $router->get('/chat', 'ChatController@chats');
             $router->get('/chat/history', 'ChatController@chatHistory');
             
            // Delete a message
            $router->delete('/chat/{messageId}', 'ChatController@deleteMessage');
        });
        // Tournaments APIs
        $router->group(['middleware' => 'auth:api'], function () use ($app) {
            // Get all new / joined / finished tournaments
            $router->get('/tournaments/all', 'TournamentController@getAllEventsList');
            // Get all new tournaments user didn't join
            $router->get('/tournaments', 'TournamentController@getEventsList');
            // Tournament activity details
            $router->get('/tournaments/{eventActivityId}', 'TournamentController@getEventActivityDetails');
            // Tournament activity leaderboard
            $router->get('/tournaments/{eventActivityId}/leaderboard', 'TournamentController@getEventActivityLeaderboard');
              
            // User Join the tournament
            $router->post('/user/tournaments/join', 'TournamentController@userJoinTournament');
            
            // Get all tournaments that user joined
            $router->get('/user/tournaments', 'TournamentController@getUserJoinedTournaments');
            // Get all finished tournaments that user joined
            $router->get('/user/tournaments/finished', 'TournamentController@getUserFinishedTournaments');
            
            // Get user's tournament connections who haven not joined yet
            $router->get('/user/tournaments/{eventActivityId}/connections', 'TournamentController@getUserTournamentConnections');
            // Invite connection for tournament 
            $router->post('/user/tournaments/invite', 'TournamentController@getUserTournamentInvite');
    });
    // Guidance APIs
    $router->group(['middleware' => 'auth:api'], function () use ($app) {
        // Guidance home screen
        $router->get('/guidance/home', 'GuidanceController@home');
        
        // Getting list of combos/set-routines/workouts (plans)
        $router->get('/guidance/plans/{type_id}', 'GuidanceController@getPlans');
        $router->get('/guidance/plans/{typeId}/{planId}', 'GuidanceController@getPlanDetail');
        $router->get('/guidance/essentials', 'GuidanceController@getEssentialsVideos');
        $router->get('/guidance/essentials/{id}', 'GuidanceController@getEssentialsVideoDetail');
        // Rating
        $router->post('/guidance/rate', 'GuidanceController@postRating');
    });
    // In-App Purchase APIs [these APIs don't need authorization]
    // Get IAP get list of products
    $router->get('/iap/products/{platform}', 'IapController@getProducts');
    // Store In-App Purchase receipts
    $router->post('/iap/receipt', 'IapController@storeReceipt');
    // Fan App APIs routes
    // These API does not need auth
    // Get list of Fan APP Companies
    $router->get('/fan/companies', 'CompanyController@getCompanyList');
    // FAN App admin register
    $router->post('/fan/user/register', 'FanUserController@registerFanAdmin');
    // FAN App admin login
    $router->post('/fan/auth/login', 'FanUserController@authenticate');
    // FAN App APIs
    $router->group(['middleware' => 'auth:fan'], function() use ($app) {
        // Get my events list
        $router->get('/fan/events', 'EventController@getMyEventsList');
        // Get list of all of the events
        $router->get('/fan/events/all', 'EventController@getAllEventsList');
        // New Event
        $router->post('/fan/events', 'EventController@postEvent');
        // Update an Event
        $router->post('/fan/events/{eventId}', 'EventController@postUpdateEvent');
        
        // Delete an event
        $router->delete('/fan/events/{eventId}', 'EventController@deleteEvent');
        
        // Get fan activity types
        $router->get('/fan/activities', 'EventController@getEventActivityTypes');
        // Get user's event list by country id for fan APP 
        $router->get('/fan/users', 'FanUserController@getUsersList');
        
        // Register user to db
        $router->post('/fan/users', 'FanUserController@postUserToDb');
        
        // Get list of event activities with users
        $router->get('/fan/events/{eventId}/activities', 'EventController@getEventActivities');
        // Create activity into event
        $router->post('/fan/events/{eventId}/activities', 'EventController@postAddEventActivity');
        // Remove activity
        $router->delete('/fan/events/{eventId}/activities', 'EventController@deleteEventActivity');
        // List of locations
        $router->get('/fan/locations', 'LocationController@getLocationsList');
        // Change password
        $router->post('/fan/user/change_password', 'FanUserController@setFanUserPassword');
        
        // Get Event Activity participants
        $router->get('/fan/events/activities/{eventActivityId}/users', 'EventController@getEventActivityParticipants');
        // Add user to Event Activity
        $router->post('/fan/events/activities/users', 'EventController@postUsersToEventActivity');
        
        // Remove user from Event Activity
        $router->delete('/fan/events/activities/users', 'EventController@deleteUsersFromEventActivity');
        // Authorize user for Event Activity
        $router->post('/fan/events/activities/users/authorize', 'EventController@authorizeUserForEventActivity');
        // Store event activity sessoins' punches data
        $router->post('/fan/events/activities/sessions', 'EventController@storeEventSessions');
        
        // Get event activity leaderboard 
        $router->get('/fan/events/activities/{eventActivityId}/leaderboard', 'EventController@getLeaderboardByEventActivity');
         
        // Update activity status
        $router->post('/fan/events/activities/status', 'EventController@postStatusUpdateEventActivity');
  //  });
});
