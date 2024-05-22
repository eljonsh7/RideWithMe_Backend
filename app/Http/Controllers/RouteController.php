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

/**
 * @OA\Schema(
 *     schema="Route",
 *     type="object",
 *     title="Route",
 *     description="Route model",
 *     required={"id", "driver_id", "city_from_id", "city_to_id", "location_id", "datetime", "passengers_number", "price"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Route ID"),
 *     @OA\Property(property="driver_id", type="string", format="uuid", description="Driver ID"),
 *     @OA\Property(property="city_from_id", type="string", format="uuid", description="City from ID"),
 *     @OA\Property(property="city_to_id", type="string", format="uuid", description="City to ID"),
 *     @OA\Property(property="location_id", type="string", format="uuid", description="Location ID"),
 *     @OA\Property(property="datetime", type="string", format="date-time", description="Date and time of the route"),
 *     @OA\Property(property="passengers_number", type="integer", description="Number of passengers"),
 *     @OA\Property(property="price", type="number", format="float", description="Price")
 * )
 */
class RouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1/routes/get",
     *     tags={"Route"},
     *     summary="Get routes",
     *     description="Get a list of routes",
     *     @OA\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", format="int32")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", format="int32")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Route")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/v1/routes/search",
     *     tags={"Route"},
     *     summary="Search routes",
     *     description="Search for routes based on criteria",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="cityFromId", type="integer", description="ID of the city from"),
     *             @OA\Property(property="cityToId", type="integer", description="ID of the city to"),
     *             @OA\Property(property="date", type="string", format="date", description="Date of the route (optional)"),
     *             @OA\Property(property="time", type="string", format="time", description="Time of the route (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Route")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/v1/routes/add",
     *     tags={"Route"},
     *     summary="Add route",
     *     description="Add a new route",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Route")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Route added successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Route")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

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

    /**
     * @OA\Delete(
     *     path="/v1/routes/delete/{id}",
     *     tags={"Route"},
     *     summary="Delete route",
     *     description="Delete a route by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the route to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Route deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Route not found"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/v1/routes/{id}",
     *     tags={"Route"},
     *     summary="Get route by ID",
     *     description="Get route information by route ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the route",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Route")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Route not found"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/v1/routes/user/{driverId}",
     *     tags={"Route"},
     *     summary="Get routes by driver ID",
     *     description="Get routes of a user by driver ID",
     *     @OA\Parameter(
     *         name="driverId",
     *         in="path",
     *         description="ID of the driver",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Route")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No routes found for the driver"
     *     )
     * )
     */
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
