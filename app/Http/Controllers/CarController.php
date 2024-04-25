<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CarController extends Controller
{
    use Illuminate\Support\Facades\Storage;

    public function store(Request $request)
    {
        $request->validate([
            'brand' => 'required|string',
            'serie' => 'required|string',
            'type' => 'required|string',
            'thumbnail' => 'nullable|image',
            'seatsNumber' => 'required|string',
        ]);

        try {
            $car = Car::where('brand', $request->brand)
                ->where('serie', $request->serie)
                ->where('type', $request->type)
                ->first();

            if ($car) {
                return response()->json(['message' => 'This car already exists.'], 401);
            }

            $car = new Car();
            $car->id = Str::uuid();
            $car->brand = $request->brand;
            $car->serie = $request->serie;
            $car->type = $request->type;
            $car->seats_number = $request->seatsNumber;

            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
                $car->thumbnail = $thumbnailPath;
            }

            $car->save();

            return response()->json(['message' => 'Car created successfully', 'car' => $car], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($carId){
        $car = Car::findOrFail($carId);
        if($car){
            $car->delete();
            return response()->json(['message'=>'Car deleted successfully.'],200);
        }
        return response()->json(['message'=>'Car not found.'],404);
    }

    public function getAllCars()
    {
        try {
            $cars = Car::get();
            return response()->json(['cars' => $cars], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'brand' => 'nullable|string',
                'serie' => 'nullable|string',
                'type' => 'nullable|string',
                'thumbnail' => 'nullable|image',
                'seatsNumber' => 'nullable|string',
            ]);

            $car = Location::findOrFail($id);

            $fillableFields = ['brand', 'serie', 'type', 'seats_number',];
            foreach ($fillableFields as $field) {
                if ($request->filled($field)) {
                    $car->$field = $request->$field;
                }
            }

            if ($request->filled('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
                $car->thumbnail = $thumbnailPath;
            }

            $car->save();

            return response()->json(['message' => 'Car updated successfully', 'car' => $car], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
