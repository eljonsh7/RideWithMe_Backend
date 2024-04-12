<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Public routes
Route::post('v1/login', [UserController::class, 'login']);
Route::post('v1/signup', [UserController::class, 'signup']);

//Middleware routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('users/get', [UserController::class, 'getAllUsers']);
    Route::put('users/update/{id}', [UserController::class, 'updateUser']);

    Route::post('cities/store', [CityController::class, 'storeCity']);
    Route::delete('cities/delete/{cityId}', [CityController::class, 'deleteCity']);
    Route::get('cities/get', [CityController::class, 'getAllCities']);
    Route::put('cities/update/{id}', [CityController::class, 'updateCity']);

    Route::post('locations/store', [CityController::class, 'storeLocation']);
    Route::delete('locations/delete/{locationId}', [CityController::class, 'deleteLocation']);
    Route::get('locations/get', [CityController::class, 'getAllLocations']);
    Route::put('locations/update/{id}', [CityController::class, 'updateLocation']);

    Route::get('/routes/get', [RouteController::class,'index']);
    Route::get('/routes/search', [RouteController::class,'search']);
    Route::post('/routes/add', [RouteController::class, 'addRoute']);
    Route::delete('/routes/delete/{id}', [RouteController::class, 'deleteRoute']);
    Route::get('/routes/{id}', [RouteController::class, 'getRoute']);
    Route::get('/routes/user/{id}', [RouteController::class, 'getUserRoutes']);
});
