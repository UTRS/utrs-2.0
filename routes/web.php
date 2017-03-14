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

Route::auth();

Route::get('/oauth/initiate', 'Auth\LoginController@redirectToProvider');
Route::get('/oauth/callback', 'Auth\LoginController@handleProviderCallback');

//Appeals Submit screens
Route::get('/', 'AppealController@index');										//PAGE
Route::post('/', 'AppealController@create');									//PAGE
Route::get('/blockinfo/{username}', 'mediaWikiCacheController@getUserInfo');	//AJAX

//Home
Route::get('/home', 'HomeController@index');									//PAGE

//Appeals
Route::get('/appeal/{appeal}', 'AppealController@view');						//PAGE
Route::post('/appeal/{appeal}/addComment', 'AppealController@addComment');		//AJAX
Route::put('/appeal/{appeal}/statusChange', 'AppealController@statusChange');	//AJAX

//Unblock advice
Route::get('/block/notices/anon', function() {
	return view('Appeal.applicants.advice.anon');
});
Auth::routes();

Route::get('/home', 'HomeController@index');
