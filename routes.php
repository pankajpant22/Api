<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('home', function () {
    return view('home');
});

Route::get('testgcm', 'GcmController@index');
Route::post('testgcm', 'GcmController@sendMessage');



/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['prefix' => 'api'], function () {
  Route::get('users', 'UsersController@index');
  Route::post('login', 'LoginController@index');
  Route::post('createuser', 'LoginController@store');
  Route::post('saveevent', 'IncidenceController@store');
  Route::post('registergcm', 'UsersController@store');
  Route::post('updatelocation', 'IncidenceController@updatelocation');

  Route::post('getincidences', 'IncidenceController@getincidences');
  
  Route::post('updategcmlocation', 'IncidenceController@updategcmlocation');

//  Route::post('project/{id}', 'ProjectController@index');

});
