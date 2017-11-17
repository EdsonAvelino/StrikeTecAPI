<?php

Route::group([
    'prefix'     => 'videos',
    'as'         => 'videos.',
    'namespace'  => 'Videos',
], function () {
    
    /* Start video route */
    Route::get('upload', 'VideosController@upload')->name('upload');
    Route::get('listing/{id?}', 'VideosController@listing')->name('list');
    Route::post('save', 'VideosController@save');
    
    /* Start category route */
    Route::get('category/edit/{id?}', 'VideosCategoryController@edit');
    Route::get('category/delete/{id}', 'VideosCategoryController@delete');
    Route::get('category/listing', 'VideosCategoryController@listing')->name('category.list');
    Route::post('category/update/{id}', 'VideosCategoryController@update');
    Route::post('category/save/', 'VideosCategoryController@save');
    Route::get('category/create', 'VideosCategoryController@create')->name('category.create');    
});