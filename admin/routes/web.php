<?php

/**
 * Global Routes
 * Routes that are used between both frontend and backend.
 */

// Switch between the included languages
Route::get('lang/{lang}', 'LanguageController@swap');


/* ----------------------------------------------------------------------- */

/*
 * Frontend Routes
 * Namespaces indicate folder structure
 */
Route::group(['namespace' => 'Frontend', 'as' => 'frontend.'], function () {
    includeRouteFiles(__DIR__.'/Frontend/');
});

/* ----------------------------------------------------------------------- */

/*
 * Backend Routes
 * Namespaces indicate folder structure
 */
Route::group(['namespace' => 'Backend', 'prefix' => 'admin', 'as' => 'admin.', 'middleware' => 'admin'], function () {
    /*
     * These routes need view-backend permission
     * (good if you want to allow more than one group in the backend,
     * then limit the backend features by different roles or permissions)
     *
     * Note: Administrator has all permissions so you do not have to specify the administrator role everywhere.
     */
    
    
    includeRouteFiles(__DIR__.'/Backend/');
});

//Route::get('admin/video/edit/{id?}', ['uses' => 'Backend\Videos\VideosController@edit']);
//Route::get('admin/video/delete/{id}', ['uses' => 'Backend\Videos\VideosController@delete']);
//Route::post('admin/video/update/{id}', ['uses' => 'Backend\Videos\VideosController@update']);


/* Start Subscription route */
Route::post('registersubscription', 'Backend\Subscriptionplan\SubcriptionController@registerSubcription')->name('register.subs');
Route::post('editsubscription', 'Backend\Subscriptionplan\SubcriptionController@editSubcription')->name('edit.subs');
Route::get('subscription/{id?}', 'Backend\Subscriptionplan\SubcriptionController@deleteSubcription')->name('delete.subs');


Route::get('/home', 'HomeController@index')->name('home');

Route::post('addsubscripionapi', 'Backend\Subscriptionplan\SubcriptionController@addSubcriptionAPI')->name('addsubscriptionapi');
