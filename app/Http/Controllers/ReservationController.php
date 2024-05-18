<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Exception;
use Illuminate\Http\Request;

class ReservationController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'route_id' => 'required|exists:routes,id',
            'status' => 'required|string',
            'seat' => 'required|integer',
        ]);

        $reservation = Reservation::where('user_id', $request->user_id)
        ->where('route_id', $request->route_id)
        ->exists();

        if($reservation){
            return response()->json(['message' => 'Reservation already exists!'], 409);
        }
        $reservation = Reservation::create($request->all());
        return response()->json($reservation, 201);
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
