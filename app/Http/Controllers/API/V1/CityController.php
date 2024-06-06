<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\API\V1\City\DeleteCityRequest;
use App\Http\Requests\API\V1\City\GetCitiesRequest;
use App\Http\Requests\API\V1\City\GetCityRequest;
use App\Http\Requests\API\V1\City\StoreCityRequest;
use App\Http\Requests\API\V1\City\UpdateCityRequest;
use App\Models\City;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="City",
 *     type="object",
 *     title="City",
 *     description="City model",
 *     required={"id", "name", "country"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Primary key of the city"),
 *     @OA\Property(property="name", type="string", description="Name of the city"),
 *     @OA\Property(property="country", type="string", description="Country of the city"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the city was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the city was updated"),
 * )
 */

class CityController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/cities/store",
     *     tags={"City"},
     *     security={{"bearerAuth": {}}},
     *     summary="Create city",
     *     description="Create a new city",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "country"},
     *             @OA\Property(property="name", type="string", description="Name of the city"),
     *             @OA\Property(property="country", type="string", description="Country of the city")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="City created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="City created successfully"),
     *             @OA\Property(property="city", ref="#/components/schemas/City")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="A city with this name already exists"
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
    public function store(StoreCityRequest $request)
    {
        $data = $request->validated();

        try {
            $city = City::where('name', $data['name'])->where('country', $data['country'])->first();
            if($city){
                return response()->json(['message' => 'A city with this name already exists.'], 401);
            }

            $city = new City();
            $city->id = Str::uuid();
            $city->name = $data['name'];
            $city->country = $data['country'];
            $city->save();

            return response()->json(['message' => 'City created successfully', 'city' => $city], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }

    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cities/delete/{cityId}",
     *     tags={"City"},
     *     security={{"bearerAuth": {}}},
     *     summary="Delete city",
     *     description="Delete a city by ID",
     *     @OA\Parameter(
     *         name="cityId",
     *         in="path",
     *         required=true,
     *         description="ID of the city to be deleted",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="City deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="City not found"
     *     )
     * )
     */

    public function delete(DeleteCityRequest $request, $cityId){
        $request->validated();
        $city = City::findOrFail($cityId);
        if($city){
            $city->delete();
            return response()->json(['message'=>'City deleted successfully.'],200);
        }
        return response()->json(['message'=>'City not found.'],404);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cities/get",
     *     tags={"City"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all cities",
     *     description="Get a list of all cities",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/City")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */
    public function getAllCities(GetCitiesRequest $request)
    {
        try {
            $request->validated();
            $cities = City::get();
            return response()->json(['cities' => $cities], 200);
        }catch (Exception $e){
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cities/update/{id}",
     *     tags={"City"},
     *     security={{"bearerAuth": {}}},
     *     summary="Update city",
     *     description="Update a city by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the city to be updated",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "country"},
     *             @OA\Property(property="name", type="string", description="Name of the city"),
     *             @OA\Property(property="country", type="string", description="Country of the city")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="City updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="City updated successfully"),
     *             @OA\Property(property="city", ref="#/components/schemas/City")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="City not found"
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
    public function update(UpdateCityRequest $request,$id)
    {
        try {
            $data = $request->validated();

            $city = City::findOrFail($id);

            $fillableFields = ['name', 'country'];
            foreach ($fillableFields as $field) {
                if (isset($data[$field])) {
                    $city->$field = $data[$field];
                }
            }
            $city->save();

            return response()->json(['message' => 'City updated successfully', 'city' => $city], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cities/{id}",
     *     tags={"City"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get city by ID",
     *     description="Get a city by its ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the city to be retrieved",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/City")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="City not found"
     *     )
     * )
     */
    public function getCity(GetCityRequest $request, $id)
    {
        $request->validated();
        $city = City::find($id);

        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $city
        ], 200);
    }
}
