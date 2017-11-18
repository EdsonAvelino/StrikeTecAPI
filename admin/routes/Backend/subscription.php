<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Route::group([
    'prefix'     => '',
    'as'         => 'subscriptionplan.',
    'namespace'  => 'Subscriptionplan',
], function(){
    
    Route::get('addsubscription/{id?}', 'SubcriptionController@addSubcriptionUI')->name('add');
    Route::get('subscriptions', 'SubcriptionController@listSubcriptionUI')->name('list.subs');
});

