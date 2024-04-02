<?php

use App\Http\Controllers\LocationController;
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
    Route::post('cities/store', [LocationController::class, 'storeCity']);
    Route::delete('cities/delete/{cityId}', [LocationController::class, 'deleteCity']);
    Route::post('locations/store', [LocationController::class, 'storeLocation']);
    Route::delete('locations/delete/{locationId}', [LocationController::class, 'deleteLocation']);
});
