<?php

namespace App\Http\Controllers\API\V1;

use App\Events\NotificationEvent;
use App\Http\Requests\API\V1\Reservation\GetReceivedRequestsRequest;
use App\Http\Requests\API\V1\Reservation\GetRouteRequestsRequest;
use App\Http\Requests\API\V1\Reservation\GetSentRequestsRequest;
use App\Http\Requests\API\V1\Reservation\StoreReservationRequest;
use App\Http\Requests\API\V1\Reservation\UpdateReservationRequest;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\Route;
use Exception;
use Illuminate\Http\Request;


/**
 * @OA\Schema(
 *     schema="Reservation",
 *     type="object",
 *     title="Reservation",
 *     description="Reservation model",
 *     required={"id", "user_id", "route_id", "status", "seat"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Primary key of the reservation"),
 *     @OA\Property(property="user_id", type="string", format="uuid", description="ID of the user making the reservation"),
 *     @OA\Property(property="route_id", type="string", format="uuid", description="ID of the route being reserved"),
 *     @OA\Property(property="status", type="string", description="Status of the reservation"),
 *     @OA\Property(property="seat", type="integer", description="Seat number"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the reservation was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the reservation was updated"),
 * )
 */
class ReservationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/reservations/create",
     *     tags={"Reservation"},
     *     security={{"bearerAuth": {}}},
     *     summary="Create reservation",
     *     description="Create a new reservation",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "route_id", "status", "seat"},
     *             @OA\Property(property="user_id", type="integer", description="ID of the user"),
     *             @OA\Property(property="route_id", type="integer", description="ID of the route"),
     *             @OA\Property(property="status", type="string", description="Status of the reservation"),
     *             @OA\Property(property="seat", type="integer", description="Seat number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reservation created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Reservation")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Reservation already exists"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */

    public function store(StoreReservationRequest $request, $route)
    {
        $data = $request->validated();
        $sender = auth()->user();

        $reservation = Reservation::where('user_id', $sender->id)
            ->where('route_id', $route)
            ->exists();

        if ($reservation) {
            return response()->json(['message' => 'Reservation already exists!'], 409);
        }
        $reservation = Reservation::create([
                'user_id' => $sender->id,
                'route_id' => $route,
                'status' => 'requested',
                'seat' => $data['seat']
            ]
        );
        $route = Route::where('id', $route)->with('driver')->first();
        unset($sender->password);
        $notificationData = Notification::create([
            'user_id' => $route->driver->id,
            'sender_id' => $sender->id,
            'type' => 'routeReservationRequested',
        ]);

        $notificationEventData = [
            'id' => $notificationData->id,
            'user_id' => $notificationData->user_id,
            'sender_id' => $notificationData->sender_id,
            'message' => $sender->first_name . " " . $sender->last_name . ' sent you a route reservation request.',
        ];
        broadcast(new NotificationEvent($notificationEventData))->toOthers();
        return response()->json($reservation, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/reservations/update/{reservation}",
     *     tags={"Reservation"},
     *     security={{"bearerAuth": {}}},
     *     summary="Update reservation",
     *     description="Update the status of a reservation",
     *     @OA\Parameter(
     *         name="reservation",
     *         in="path",
     *         description="ID of the reservation",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", description="Status of the reservation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservation status updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Reservation")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reservation not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */

    public function update(UpdateReservationRequest $request, $reservation)
    {
        $data = $request->validated();
        try {
            $reservation = Reservation::where('id', $reservation)->first();
            $reservation->status = $data['status'];
            $reservation->save();
            $sender = auth()->user();
            unset($sender->password);
            $notificationData = Notification::create([
                'user_id' => $reservation->user_id,
                'sender_id' => $sender->id,
                'type' => 'routeReservation'.ucfirst($reservation->status),
            ]);

            $notificationEventData = [
                'id' => $notificationData->id,
                'user_id' => $notificationData->user_id,
                'sender_id' => $notificationData->sender_id,
                'message' => $sender->first_name . " " . $sender->last_name . ' ' . $reservation->status . ' reservation.',
            ];
            broadcast(new NotificationEvent($notificationEventData))->toOthers();
            return response()->json(['message' => 'Reservation ' . $reservation->status . ".", 'reservation' => $reservation], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reservations/received",
     *     tags={"Reservation"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get received requests",
     *     description="Get a list of received reservation requests",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Reservation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */

    public function getReceivedRequests(GetReceivedRequestsRequest $request)
    {
        $request->validated();
        $user = auth()->user();
        try {
            $reservations = Reservation::where('status', 'requested')
                ->with('route')->with('user')
                ->whereHas('route.driver', function ($query) use ($user) {
                    $query->where('id', $user->id);
                })
                ->get();

            return response()->json(['message' => 'Reservations fetched successfully.', 'reservations' => $reservations], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reservations/route/{routeId}",
     *     tags={"Reservation"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get reservations for a specific route",
     *     description="Get a list of reservations for a specific route",
     *     @OA\Parameter(
     *         name="routeId",
     *         in="path",
     *         description="ID of the route",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reservations fetched successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Reservation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred."),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */

    public function getRouteRequests(GetRouteRequestsRequest $request, $routeId)
    {
        try {
            $request->validated();
            $reservations = Reservation::where('route_id', $routeId)
                ->with(['route', 'user'])
                ->get();

            return response()->json(['message' => 'Reservations fetched successfully.', 'reservations' => $reservations], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reservations/sent",
     *     tags={"Reservation"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get sent requests",
     *     description="Get a list of sent reservation requests",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Reservation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */
    public function getSentRequests(GetSentRequestsRequest $request)
    {
        $request->validated();
        $user = auth()->user();
        try {
            $reservations = Reservation::where('user_id', $user->id)
                ->where('status', '!=', 'canceled')
                ->where('status', '!=', 'rejected')
                ->with(['route.driver', 'route.cityFrom', 'route.cityTo'])
                ->get();

            // Extract routes from reservations
            $routes = $reservations->map(function ($reservation) {
                return $reservation->route;
            });

            return response()->json(['message' => 'Reservations fetched successfully.', 'routes' => $routes], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

}
