<?php


Route::get('/', 'GroupController@index');
Route::post('/make', 'GroupController@makeGroup')->name('makeGroup');