<?php

use App\Http\Controllers\CarController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserFeedbackController;
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

Route::get('v1/routes/get', [RouteController::class,'index']);
Route::get('v1/cities/get', [CityController::class, 'getAllCities']);

//Middleware routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('users/getByToken', [UserController::class, 'getUserByToken']);
    Route::get('users/get', [UserController::class, 'getAllUsers']);
    Route::get('users/{id}', [UserController::class, 'getUser']);
    Route::put('users/update/{userId}', [UserController::class, 'update']);
    Route::delete('users/delete/{userId}', [UserController::class, 'delete']);
    Route::post('users/ban/{userId}', [UserController::class, 'ban']);
    Route::delete('users/ban/remove/{userId}', [UserController::class, 'removeBan']);

    Route::post('users/car/attach', [UserController::class, 'attachCar']);

    Route::post('media/store', [MediaController::class, 'store']);

    Route::post('cities/store', [CityController::class, 'store']);
    Route::delete('cities/delete/{cityId}', [CityController::class, 'delete']);
    Route::put('cities/update/{id}', [CityController::class, 'update']);
    Route::post('cities/{id}', [CityController::class, 'getCity']);

    Route::post('locations/store', [LocationController::class, 'store']);
    Route::delete('locations/delete/{locationId}', [LocationController::class, 'delete']);
    Route::get('locations/get/{cityId}', [LocationController::class, 'getAllLocations']);
    Route::get('locations/{locationId}', [LocationController::class, 'getLocation']);
    Route::put('locations/update/{id}', [LocationController::class, 'update']);

    Route::post('cars/store', [CarController::class, 'store']);
    Route::delete('cars/delete/{carId}', [CarController::class, 'delete']);
    Route::get('cars/get', [CarController::class, 'getAllCars']);
    Route::put('cars/update/{id}', [CarController::class, 'update']);

    Route::post('routes/search', [RouteController::class,'search']);
    Route::post('routes/add', [RouteController::class, 'addRoute']);
    Route::post('routes/get', [RouteController::class, 'index']);
    Route::delete('routes/delete/{id}', [RouteController::class, 'deleteRoute']);
    Route::get('routes/{id}', [RouteController::class, 'getRoute']);
    Route::get('routes/user/{id}', [RouteController::class, 'getUserRoutes']);

    Route::post('reservations/create',[ReservationController::class,'store']);
    Route::put('reservations/update/{reservation}',[ReservationController::class,'update']);
    Route::get('reservations/received',[ReservationController::class,'getReceivedRequests']);
    Route::get('reservations/sent',[ReservationController::class,'getSentRequests']);

    Route::post('messages/send/{recipient}',[ChatController::class,'sendMessage']);
    Route::get('messages/get/{recipient}/{type}',[ChatController::class,'getConversation']);
    Route::get('messages/get/last',[ChatController::class,'getConversationsWithMessages']);
    Route::put('messages/read/{recipient}',[ChatController::class,'markConversationAsRead']);
    Route::delete('messages/delete/{recipient}',[ChatController::class,'deleteConversation']);

    Route::post('messages/group/store',[GroupChatController::class,'store']);
    Route::post('messages/group/send/{group}',[GroupChatController::class,'sendMessageToGroup']);

    Route::get('members/get/{group}',[GroupChatController::class,'retrieveAllGroupMembers']);

    Route::post('friends/request/{user}',[FriendController::class,'sendFriendRequest']);
    Route::put('friends/accept/{user}',[FriendController::class,'acceptFriendRequest']);
    Route::delete('friends/decline/{user}',[FriendController::class,'declineFriendRequest']);
    Route::delete('friends/cancel/{user}',[FriendController::class,'cancelFriendRequest']);
    Route::delete('friends/unfriend/{user}',[FriendController::class,'unfriend']);

    Route::post('ratings/add/{user}',[UserFeedbackController::class,'addRating']);
    Route::put('ratings/update/{user}',[UserFeedbackController::class,'updateRating']);
    Route::delete('ratings/delete/{user}',[UserFeedbackController::class,'deleteRating']);

    Route::post('reports/add/{user}',[UserFeedbackController::class,'addReport']);
    Route::delete('reports/delete/{user}',[UserFeedbackController::class,'deleteReport']);

});

