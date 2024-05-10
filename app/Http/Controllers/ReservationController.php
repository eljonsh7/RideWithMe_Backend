<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Exception;
use Illuminate\Http\Request;

class ReservationController extends Controller
{

    public function store($route)
    {
        $user = auth()->user();
        try {
            $reservation = Reservation::create([
                'user_id' => $user->id,
                'route_id' => $route,
                'status' => 'requested'
            ]);
            return response()->json(['message' => 'Reservation requested.', 'reservation' => $reservation], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $reservation)
    {
        $request->validate([
            'status' => 'required|string'   // status values(except requested): accepted, canceled, rejected
        ]);                                 // Drivers can set 2 status values: accepted, rejected, while passengers : requested, canceled
        try {
            $reservation = Reservation::find($reservation)->first();
            $reservation->status = $request->status;
            $reservation->save();
            return response()->json(['message' => 'Reservation ' . $request->status . ".", 'reservation' => $reservation], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getReceivedRequests()
    {
        $user = auth()->user();
        try {
            $reservations = Reservation::where('status','requested')
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

    public function getSentRequests()
    {
        $user = auth()->user();
        try {
            $reservations = Reservation::where('user_id', $user->id)
                ->where('status', '!=', 'canceled')
                ->where('status', '!=', 'rejected')
                ->with('route')
                ->get();
            return response()->json(['message' => 'Reservations fetched successfully.', 'reservations' => $reservations], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

}
