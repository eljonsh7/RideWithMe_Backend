<?php

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
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
