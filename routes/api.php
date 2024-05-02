<?php

use App\Http\Controllers\CarController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\UserController;
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
    Route::put('users/update/{userId}', [UserController::class, 'update']);
    Route::delete('users/delete/{userId}', [UserController::class, 'delete']);
    Route::post('users/ban/{userId}', [UserController::class, 'ban']);
    Route::delete('users/ban/remove/{userId}', [UserController::class, 'removeBan']);

    Route::post('media/store', [MediaController::class, 'store']);

    Route::post('cities/store', [CityController::class, 'store']);
    Route::delete('cities/delete/{cityId}', [CityController::class, 'delete']);
    Route::get('cities/get', [CityController::class, 'getAllCities']);
    Route::put('cities/update/{id}', [CityController::class, 'update']);

    Route::post('locations/store', [LocationController::class, 'store']);
    Route::delete('locations/delete/{locationId}', [LocationController::class, 'delete']);
    Route::get('locations/get/{cityId}', [LocationController::class, 'getAllLocations']);
    Route::put('locations/update/{id}', [LocationController::class, 'update']);

    Route::post('cars/store', [CarController::class, 'store']);
    Route::delete('cars/delete/{carId}', [CarController::class, 'delete']);
    Route::get('cars/get', [CarController::class, 'getAllCars']);
    Route::put('cars/update/{id}', [CarController::class, 'update']);

    Route::get('routes/get', [RouteController::class,'index']);
    Route::post('routes/search', [RouteController::class,'search']);
    Route::post('routes/add', [RouteController::class, 'addRoute']);
    Route::delete('routes/delete/{id}', [RouteController::class, 'deleteRoute']);
    Route::get('routes/{id}', [RouteController::class, 'getRoute']);
    Route::get('routes/user/{id}', [RouteController::class, 'getUserRoutes']);
});
