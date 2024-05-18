<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CityController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReservationController;
use App\Models\Conversation;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Models\Route;
use Illuminate\Support\Carbon;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');
        $query = Route::query()->where('datetime', '>', $currentDateTime);

        $routes = $query->paginate($request->pageSize, ['*'], 'page', $request->page);

        $routes = $this->formatRoutes($routes);
        return response()->json($routes,200);
    }

    private function formatRoutes($routes){
        $routes->getCollection()->transform(function ($route) {
            $this->formatRoute($route);
            return $route;
        });
        return $routes;
    }
    private function formatRoute($route){
        $route->load('cityFrom', 'cityTo', 'driver', 'location');
    
        $route->city_from = $route->cityFrom;
        $route->city_to = $route->cityTo;
    
        unset($route->city_from_id);
        unset($route->city_to_id);
        unset($route->driver_id);
        unset($route->location_id);
    
        return $route;
    }
    public function search(Request $request){
        $request->validate([
            'cityFromId' => 'required',
            'cityToId' => 'required',
        ]);
        $query = Route::query()
            ->where('city_from_id', $request->cityFromId)
            ->where('city_to_id', $request->cityToId);

        if ($request->has('date')) {
            $date = $request->date;
            if ($request->has('time')) {
                $time = $request->time;
                $datetime = "$date $time";
                $query->where('datetime', '>=', $datetime)
                    ->whereDate('datetime', '=', $date);
            } else {
                $query->whereDate('datetime', '=', $date);
            }
        }

        if ($request->has('page')) {
            $query->paginate($request->pageSize);
        } else {
            $query->get();
        }

        $routes = $query->paginate($request->pageSize, ['*'], 'page', $request->page);

        $routes = $this->formatRoutes($routes);

        return response()->json($routes, 200);
    }

    public function addRoute(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'driver_id' => 'required|exists:users,id',
            'city_from_id' => 'required|exists:cities,id',
            'city_to_id' => 'required|exists:cities,id',
            'location_id' => 'required|exists:locations,id',
            'datetime' => 'required|date_format:Y-m-d H:i:s',
            'passengers_number' => 'required|integer|min:1',
            'price' => 'required'
        ]);

        $route = Route::create($request->all());
        $this->addGroup($user,$route);
        return response()->json($route, 201);
    }

    public function deleteRoute($id)
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        $route->delete();

        return response()->json([
            'success' => true,
            'message' => 'Route deleted successfully'
        ], 200);
    }

    public function getRoute($id)
    {
        $user = auth()->user();
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }
        $route = $this->formatRoute($route);

        $reservations = Route::with(["reservations"=> function($query) {
            $query->where('status', 'Approved');
        }])->find($route->id)->reservations;
        
        $route->takenSeats = $reservations->pluck('seat')->toArray();
        $reservFromUser = Route::with(['reservations' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])->find($route->id)->reservations->first();
        $route->takenSeatByUser = $reservFromUser ? ['seat' =>$reservFromUser->seat,'status'=>$reservFromUser->status]:null;
        return response()->json($route, 200);
    }

    public function getUserRoutes($driverId)
    {
        $routes = Route::where('driver_id', $driverId)->paginate(6);

        $routes = $this->formatRoutes($routes);

        return response()->json($routes, 200);
    }

    public function addGroup($user,$route)
    {
        $group = Group::create([
            'route_id' => $route
        ]);

        Conversation::create([
            'sender_id' => $user->id,
            'recipient_id' => $group->id,
            'unread_messages' => 0,
            'type' => 'group'
        ]);
    }
}
