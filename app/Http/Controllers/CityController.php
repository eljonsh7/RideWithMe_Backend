<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Location;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CityController extends Controller
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

    public function getAllCities()
    {
        try {
            $cities = City::get();
            return response()->json(['cities' => $cities], 200);
        }catch (Exception $e){
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCity(Request $request,$id)
    {
        try {
            $request->validate([
                'name' => 'nullable|string',
                'country' => 'nullable|string'
            ]);

            $city = City::findOrFail($id);

            $fillableFields = ['name', 'country'];
            foreach ($fillableFields as $field) {
                if ($request->filled($field)) {
                    $city->$field = $request->$field;
                }
            }
            $city->save();

            return response()->json(['message' => 'City updated successfully', 'city' => $city], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function storeLocation(Request $request)
    {
        $request->validate([
            'cityId' => 'required|string',
            'name' => 'required|string',
            'googleMapsLink' => 'string'
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

    public function deleteLocation($locationId){
        $location = Location::findOrFail($locationId);
        if($location){
            $location->delete();
            return response()->json(['message'=>'Location deleted successfully.'],200);
        }
        return response()->json(['message'=>'Location not found.'],404);
    }

    public function getAllLocations()
    {
        try {
            $locations = Location::get();
            return response()->json(['locations' => $locations], 200);
        }catch (Exception $e){
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateLocation(Request $request,$id)
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
