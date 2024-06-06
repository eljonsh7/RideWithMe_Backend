<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *      schema="Car",
 *      type="object",
 *      title="Car",
 *      description="Car model",
 *      required={"id", "brand", "serie", "type", "seats_number", "thumbnail"},
 *      @OA\Property(property="id",format="uuid", type="string", description="Primary key of the car"),
 *      @OA\Property(property="brand", type="string", description="Brand of the car"),
 *      @OA\Property(property="serie", type="string", description="Serie of the car"),
 *      @OA\Property(property="type", type="string", description="Type of the car"),
 *      @OA\Property(property="seats_number", type="integer", description="Seats number of the car"),
 *      @OA\Property(property="thumbnail", type="string", description="Thumbnail of the car"),
 *      @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the car was created"),
 *      @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the car was updated"),
 *)
 */
class CarController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/cars/store",
     *     summary="Create a new car",
     *     tags={"Car"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="brand", type="string"),
     *             @OA\Property(property="serie", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="seats_number", type="integer"),
     *             @OA\Property(property="thumbnail", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Car created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="car", ref="#/components/schemas/Car")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized or Car already exists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'brand' => 'required|string',
            'serie' => 'required|string',
            'type' => 'required|string',
            'seats_number' => 'required',
            'thumbnail' => 'required|string',
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
            $car->seats_number = $request->seats_number;
            $car->thumbnail = $request->thumbnail;
            $car->save();

            return response()->json(['message' => 'Car created successfully', 'car' => $car], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cars/delete/{carId}",
     *     summary="Delete a car by ID",
     *     tags={"Car"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="carId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Car deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Car not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function delete($carId)
    {
        $car = Car::findOrFail($carId);
        if ($car) {
            if ($car->thumbnail) {
                $filePath = public_path('storage/' . $car->thumbnail);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $car->delete();
            return response()->json(['message'=>'Car deleted successfully.'], 200);
        }
        return response()->json(['message'=>'Car not found.'], 404);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cars/get",
     *     summary="Get all cars",
     *     tags={"Car"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of cars",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Car")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */

    public function getAllCars()
    {
        try {
            $cars = Car::get();
            return response()->json(['cars' => $cars], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cars/update/{id}",
     *     summary="Update a car by ID",
     *     tags={"Car"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="brand", type="string", nullable=true),
     *             @OA\Property(property="serie", type="string", nullable=true),
     *             @OA\Property(property="type", type="string", nullable=true),
     *             @OA\Property(property="seats_number", type="integer", nullable=true),
     *             @OA\Property(property="thumbnail", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Car updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="car", ref="#/components/schemas/Car")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Car not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'brand' => 'nullable|string',
                'serie' => 'nullable|string',
                'type' => 'nullable|string',
                'thumbnail' => 'nullable|string',
                'seats_number' => 'nullable',
            ]);

            $car = Car::findOrFail($id);

            if ($request->has('thumbnail') && $car->thumbnail) {
                $filePath = public_path('storage/' . $car->thumbnail);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $fillableFields = ['brand', 'serie', 'type', 'seats_number', 'thumbnail'];
            foreach ($fillableFields as $field) {
                if ($request->filled($field)) {
                    $car->$field = $request->$field;
                }
            }

            $car->save();

            return response()->json(['message' => 'Car updated successfully', 'car' => $car], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
