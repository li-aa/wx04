<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::any('/wx/index','TextController@index');
Route::any('/wx','TextController@wxEvent');
Route::get('/wx/token','TextController@token');

Route::get('/wx/ccc','TextController@ccc');