<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CityController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\UserController;
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

        $locationController = new LocationController();
        $cityController = new CityController();
        $UserController = new UserController();

        $routes->getCollection()->transform(function ($route) use ($UserController,$locationController,$cityController) {
            $route->city_from = $cityController->getCity($route->city_from_id)->getData()->data;
            $route->city_to = $cityController->getCity($route->city_to_id)->getData()->data;
            unset($route->city_from_id);
            unset($route->city_to_id);
            $route->driver = $UserController->getUser($route->driver);
            unset($route->driver_id);
            $route->location = $locationController->getLocation($route->location_id)->getData()->data;
            unset($route->location_id);
            return $route;
        });
        return response()->json($routes,200);
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

        $locationController = new LocationController();
        $cityController = new CityController();
        $cityFrom = $cityController->getCity($request->cityFromId)->getData()->data;
        $cityTo = $cityController->getCity($request->cityToId)->getData()->data;

        $routes = $query->paginate($request->pageSize, ['*'], 'page', $request->page);

        $UserController = new UserController();

        $routes->getCollection()->transform(function ($route) use ($cityFrom, $cityTo,$UserController,$locationController,$cityController) {
            $route->city_from = $cityFrom;
            $route->city_to = $cityTo;
            unset($route->city_from_id);
            unset($route->city_to_id);
            $route->driver = $UserController->getUser($route->driver);
            unset($route->driver_id);
            $route->location = $locationController->getLocation($route->location_id)->getData()->data;
            unset($route->location_id);
            return $route;
        });

        return response()->json($routes, 200);
    }

    public function addRoute(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
            'city_from_id' => 'required|exists:cities,id',
            'city_to_id' => 'required|exists:cities,id',
            'location_id' => 'required|exists:locations,id',
            'datetime' => 'required|date_format:Y-m-d H:i:s',
            'passengers_number' => 'required|integer|min:1',
        ]);

        $route = Route::create($request->all());

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
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $route
        ], 200);
    }

    public function getUserRoutes($driverId)
    {
        $routes = Route::where('driver_id', $driverId)->paginate(6);

        $locationController = new LocationController();
        $cityController = new CityController();
        $UserController = new UserController();

        $routes->getCollection()->transform(function ($route) use ($UserController,$locationController,$cityController) {
            $route->city_from = $cityController->getCity($route->city_from_id)->getData()->data;
            $route->city_to = $cityController->getCity($route->city_to_id)->getData()->data;
            unset($route->city_from_id);
            unset($route->city_to_id);
            $route->driver = $UserController->getUser($route->driver);
            unset($route->driver_id);
            $route->location = $locationController->getLocation($route->location_id)->getData()->data;
            unset($route->location_id);
            return $route;
        });

        return response()->json($routes, 200);
    }
}
