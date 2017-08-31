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
$app->group(['middleware' => 'auth:api'], function () use ($app) {
    $app->post('/users', 'UserController@update');
});