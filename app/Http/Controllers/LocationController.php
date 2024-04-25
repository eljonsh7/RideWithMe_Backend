<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'cityId' => 'required|string',
            'name' => 'required|string',
            'googleMapsLink' => 'nullable|string'
        ]);

        try {
            $location = Location::where('name',$request->name)->first();
            if($location){
                return response()->json(['message' => 'A location with this name already exists.'], 401);
            }
            $location = new Location();
            $location->id = Str::uuid();
            $location->city_id = $request->cityId;
            $location->name = $request->name;
            $location->google_maps_link = ($request->googleMapsLink != null) ? $request->googleMapsLink : null;

            $location->save();
            return response()->json(['message' => 'Location created successfully', 'location' => $location], 201);


        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }

    }

    public function delete($locationId){
        $location = Location::findOrFail($locationId);
        if($location){
            $location->delete();
            return response()->json(['message'=>'Location deleted successfully.'],200);
        }
        return response()->json(['message'=>'Location not found.'],404);
    }

    public function getAllLocations($cityId)
    {
        try {
            $locations = Location::where('city_id', $cityId)->get();
            return response()->json(['locations' => $locations], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request,$id)
    {
        try {
            $request->validate([
                'name' => 'nullable|string',
                'google_maps_link' => 'nullable|string'
            ]);

            $location = Location::findOrFail($id);

            $fillableFields = ['name', 'google_maps_link'];
            foreach ($fillableFields as $field) {
                if ($request->filled($field)) {
                    $location->$field = $request->$field;
                }
            }
            $location->save();

            return response()->json(['message' => 'Location updated successfully', 'location' => $location], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

}
