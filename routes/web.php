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
Route::post('/wx/index','TextController@index');
Route::any('/wx/wxEvent','TextController@wxEvent');
Route::get('token','TextController@token');

Route::get('/wx/ccc','TextController@ccc');

Route::get('/wx/guzzle1','TextController@guzzle1');
Route::get('/wx/guzzle2','TextController@guzzle2');

Route::post('/wx/menu','TextController@menu');

Route::get('/wx/media','TextController@media');
Route::get('/wx/wang','TextController@wang');
Route::get('/wx/media_insert','TextController@media_insert');
Route::get('/wx/getWxUserInfo','TextController@getWxUserInfo');
Route::get('/wx/uploadMedia','TextController@uploadMedia');
