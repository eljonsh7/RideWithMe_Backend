<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
 * @OA\Post(
 *     path="/api/v1/media/store",
 *     summary="Store media file",
 *     tags={"Media"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Media file to store",
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="media",
 *                     type="string",
 *                     format="binary",
 *                     description="Media file to upload"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="File stored successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="File stored successfully"),
 *             @OA\Property(property="file_path", type="string", example="/storage/cars/filename.ext")
 *         )
 *     )
 * )
 */
    public function store(Request $request)
    {
        $filePath = $request->file('media')->store($request->folder, 'public');
        return response()->json(['message' => 'File stored successfully', 'file_path' => $filePath], 201);
    }
}
