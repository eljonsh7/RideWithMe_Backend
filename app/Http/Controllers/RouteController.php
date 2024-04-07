<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Route;

class RouteController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'cityFromId' => 'required',
            'cityToId' => 'required',
            'date' => 'required',
        ]);

        $routes = Route::query()
            ->where('city_from_id', $request->cityFromId)->where('city_to_id', $request->cityToId)
            ->whereDate('datetime', '=', $request->date)
            ->paginate(6);
        return response()->json($routes, 200);
    }
}
