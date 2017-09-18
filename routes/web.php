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

$app->get('/test/lorem/ispum', function(){
	// echo '';
	// Illuminate\Support\Facades\Mail::raw('Hola! Whats up mate...', function($message) {
	//        $message->to(['ntestinfo@gmail.com'])->subject('[ALERT] notification');
	//    });
});

// Rest of all APIs are secured with token

// User APIs//
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Update user's profile data
    $app->post('/users', 'UserController@update');

    // Get user's information
    $app->get('/users/{userId}', 'UserController@getUser');

    // Get user's information
    $app->post('/user/preferences', 'UserController@updatePreferences');
});

// Training APIs//
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    // Training sessions list
    $app->get('/user/training/sessions', 'TrainingController@getSessions');

    // Get particular session
    $app->get('/user/training/sessions/{sessionId}', 'TrainingController@getSession');

    // Save Training sessions data to db
    $app->post('/user/training/sessions', 'TrainingController@storeSessions');

    // Get round and its punches
    $app->get('/user/training/sessions/rounds/{round_id}', 'TrainingController@getSessionsRound');

    // Get rounds by Training-Type
    $app->get('/user/training/sessions/rounds_by_training/{training_type_id}', 'TrainingController@getSessionsRoundsByTrainingType');

    // Save Training sessoins' rounds data to db
    $app->post('/user/training/sessions/rounds', 'TrainingController@storeSessionsRounds');

    // Save Training sessoins' rounds' punches data to db
    $app->post('/user/training/sessions/rounds/punches', 'TrainingController@storeSessionsRoundsPunches');
});