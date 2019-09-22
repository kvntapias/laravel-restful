<?php
use App\Http\Middleware\ApiAuthMiddleware;

Route::get('/', function () {
    return view('welcome');
});

Route::post('api/register', 'UserController@register');
Route::post('api/login', 'UserController@login');
Route::put('api/user/update', 'UserController@update');
Route::post('api/user/upload', 'UserController@upload')
    ->middleware(ApiAuthMiddleware::class);
Route::get('/api/user/avatar/{filename}', 'UserController@getImage');
Route::get('/api/user/detail/{id}', 'UserController@detail');

//RUTAS CONTROLADOR CATEGORIAS
Route::resource('/api/category', 'CategoryController');

//RUTAS CONTROLADOR POSTS
Route::resource('/api/post', 'PostController');
Route::post('api/post/upload', 'PostController@upload');
Route::get('/api/post/image/{filename}', 'PostController@getImage');