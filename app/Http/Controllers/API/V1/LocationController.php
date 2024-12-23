<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\API\V1\Location\DeleteLocationRequest;
use App\Http\Requests\API\V1\Location\GetLocationRequest;
use App\Http\Requests\API\V1\Location\GetLocationsRequest;
use App\Http\Requests\API\V1\Location\StoreLocationRequest;
use App\Http\Requests\API\V1\Location\UpdateLocationRequest;
use App\Models\Location;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


/**
 * @OA\Schema(
 *     schema="Location",
 *     type="object",
 *     title="Location",
 *     description="Location model",
 *     required={"id", "city_id", "name","google_maps_link"},
 *     @OA\Property(property="id", type="string", format="uuid", description="Primary key of the location"),
 *     @OA\Property(property="city_id", type="string", format="uuid", description="ID of the city"),
 *     @OA\Property(property="name", type="string", description="Name of the location"),
 *     @OA\Property(property="google_maps_link", type="string", nullable=true, description="Google Maps link for the location"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the location was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the location was updated"),
 * )
 */
class LocationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/locations/store",
     *     tags={"Location"},
     *     security={{"bearerAuth": {}}},
     *     summary="Create location",
     *     description="Create a new location",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cityId", "name"},
     *             @OA\Property(property="cityId", type="string", description="ID of the city"),
     *             @OA\Property(property="name", type="string", description="Name of the location"),
     *             @OA\Property(property="googleMapsLink", type="string", description="Google Maps link for the location")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Location created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Location created successfully"),
     *             @OA\Property(property="location", ref="#/components/schemas/Location")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="A location with this name already exists"
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
    public function store(StoreLocationRequest $request)
    {
        $data = $request->validated();

        try {
            $location = Location::where('name', $data['name'])->where('city_id', $data['cityId'])->first();
            if($location){
                return response()->json(['message' => 'A location with this name already exists.'], 401);
            }
            $location = new Location();
            $location->id = Str::uuid();
            $location->city_id = $data['cityId'];
            $location->name = $data['name'];
            $location->google_maps_link = ($data['googleMapsLink'] != null) ? $data['googleMapsLink'] : null;

            $location->save();
            return response()->json(['message' => 'Location created successfully', 'location' => $location], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/locations/delete/{locationId}",
     *     tags={"Location"},
     *     security={{"bearerAuth": {}}},
     *     summary="Delete location",
     *     description="Delete a location",
     *     @OA\Parameter(
     *         name="locationId",
     *         in="path",
     *         required=true,
     *         description="ID of the location to delete",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Location deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
     *     )
     * )
     */

    public function delete(DeleteLocationRequest $request, $locationId){
        $request->validated();
        $location = Location::findOrFail($locationId);
        if($location){
            $location->delete();
            return response()->json(['message'=>'Location deleted successfully.'],200);
        }
        return response()->json(['message'=>'Location not found.'],404);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/get/{cityId}",
     *     tags={"Location"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get all locations",
     *     description="Get all locations for a specific city",
     *     @OA\Parameter(
     *         name="cityId",
     *         in="path",
     *         required=true,
     *         description="ID of the city",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Locations fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="locations", type="array", @OA\Items(ref="#/components/schemas/Location"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred"
     *     )
     * )
     */

    public function getAllLocations(GetLocationsRequest $request, $cityId)
    {
        try {
            $request->validated();
            $locations = Location::where('city_id', $cityId)->get();
            return response()->json(['locations' => $locations], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/locations/{id}",
     *     tags={"Location"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get location",
     *     description="Get a location by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the location",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location fetched successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Location")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
     *     )
     * )
     */

    public function getLocation(GetLocationRequest $request, $id)
    {
        $request->validated();
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Location fetched successfully',
            'success' => true,
            'data' => $location
        ], 200);
    }


    /**
     * @OA\Put(
     *     path="/api/v1/locations/update/{id}",
     *     tags={"Location"},
     *     security={{"bearerAuth": {}}},
     *     summary="Update location",
     *     description="Update a location by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the location",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", description="Name of the location"),
     *             @OA\Property(property="googleMapsLink", type="string", description="Google Maps link for the location")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Location updated successfully"),
     *             @OA\Property(property="location", ref="#/components/schemas/Location")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Location not found"
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
    public function update(UpdateLocationRequest $request,$id)
    {
        try {
            $data = $request->validated();

            $location = Location::findOrFail($id);

            $fillableFields = ['name', 'google_maps_link'];
            foreach ($fillableFields as $field) {
                if (isset($data[$field])) {
                    $location->$field = $data[$field];
                }
            }
            $location->save();

            return response()->json(['message' => 'Location updated successfully', 'location' => $location], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

}
