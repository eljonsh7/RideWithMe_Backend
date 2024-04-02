<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function storeCity(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'country' => 'required|string'
        ]);

        try {
            $city = City::where('name',$request->name)->first();
            if($city){
                return response()->json(['message' => 'A city with this name already exists.'], 401);
            }

            $city = new City();
            $city->id = Str::uuid();
            $city->name = $request->name;
            $city->country = $request->country;

            $city->save();
            return response()->json(['message' => 'City created successfully', 'city' => $city], 201);


        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }

    }

    public function deleteCity($cityId){
        $city = City::findOrFail($cityId);
        if($city){
            $city->delete();
            return response()->json(['message'=>'City deleted successfully.'],200);
        }
        return response()->json(['message'=>'City not found.'],404);

    }

}
