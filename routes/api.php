<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\LocationController;
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
    Route::put('users/update/{id}', [UserController::class, 'updateUser']);
    Route::delete('users/delete/{userId}', [UserController::class, 'deleteUser']);

    Route::post('cities/store', [CityController::class, 'storeCity']);
    Route::delete('cities/delete/{cityId}', [CityController::class, 'deleteCity']);
    Route::get('cities/get', [CityController::class, 'getAllCities']);
    Route::put('cities/update/{id}', [CityController::class, 'updateCity']);

    Route::post('locations/store', [LocationController::class, 'storeLocation']);
    Route::delete('locations/delete/{locationId}', [LocationController::class, 'deleteLocation']);
    Route::get('locations/get', [LocationController::class, 'getAllLocations']);
    Route::put('locations/update/{id}', [LocationController::class, 'updateLocation']);

    Route::post('v1/routes', [RouteController::class,'search']);
});
