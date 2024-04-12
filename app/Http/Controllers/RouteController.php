<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Route;

class RouteController extends Controller
{
    public function index()
    {
        $routes = Route::paginate(9);
        return response()->json($routes);
    }
    public function search(Request $request)
{
    $request->validate([
        'cityFromId' => 'required',
        'cityToId' => 'required',
    ]);

    $query = Route::query()
        ->where('city_from_id', $request->cityFromId)
        ->where('city_to_id', $request->cityToId);

    // Check if 'datetime' parameter exists and validate it if present
    if ($request->has('datetime')) {
        $request->validate([
            'datetime' => 'required|date_format:Y-m-d H:i:s',
        ]);
        // Update the query to filter by datetime
        $query->where('datetime', '=', $request->datetime);
    }

    $routes = $query->paginate(9);
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

        $routes = Route::where('driver_id', $driverId)->paginate(9);

        return response()->json($routes, 200);
    }
}
