<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::auth();

Route::get('/home', 'HomeController@index');

Route::get('admin/login', 'Admin\LoginController@getLogin');
Route::post('admin/login', 'Admin\LoginController@postLogin');
Route::get('admin/register', 'Admin\LoginController@getRegister');
Route::post('admin/register', 'Admin\LoginController@postRegister');
Route::post('admin/logout', 'Admin\LoginController@logout');
Route::get('admin', 'Admin\IndexController@index');