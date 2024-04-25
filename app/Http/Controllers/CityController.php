<?php

namespace App\Http\Controllers;

use App\Models\City;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function store(Request $request)
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

    public function delete($cityId){
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

    public function update(Request $request,$id)
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
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 404);
        }
    }
}
