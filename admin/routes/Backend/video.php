<?php

Route::group([
    'prefix'     => '',
    'as'         => 'videos.',
    'namespace'  => 'Videos',
], function () {
    
    /* Start video route */
    
    //Route::get('video/{id?}', ['uses' => 'Backend\Videos\VideosController@edit']);
    Route::get('video/{id?}', 'VideosController@upload')->name('upload');
    Route::get('videos', 'VideosController@listing')->name('list');
    Route::post('save', 'VideosController@save');
    Route::post('update/{id}', 'VideosController@update');
    Route::get('delete/{id}', 'VideosController@delete');
    
    /* Start category route */
    Route::get('category', 'VideosCategoryController@create')->name('category.create');  
    Route::get('category/edit/{id?}', 'VideosCategoryController@edit');
    Route::get('category/{id?}', 'VideosCategoryController@edit');
    Route::get('category/delete/{id}', 'VideosCategoryController@delete');
    Route::get('categories', 'VideosCategoryController@listing')->name('category.list');
    Route::post('category/update/{id}', 'VideosCategoryController@update');
    Route::post('category/save/', 'VideosCategoryController@save');  
});