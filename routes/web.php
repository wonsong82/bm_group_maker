<?php


Route::get('/', 'GroupController@index');
Route::post('make', 'GroupController@makeGroup')->name('makeGroup');

Route::get('load/{result}', 'GroupController@load');
Route::get('save', 'GroupController@save');
Route::get('delete/{result}', 'GroupController@delete');
