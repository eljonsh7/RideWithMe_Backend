<?php

namespace App\Http\Controllers;

use App\Models\ReportReason;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="City",
 *     type="object",
 *     title="City",
 *     description="City model",
 *     required={"id", "name", "country"},
 *     @OA\Property(property="id", type="string", format="uuid", description="City ID"),
 *     @OA\Property(property="name", type="string", description="Name of the city"),
 *     @OA\Property(property="country", type="string", description="Country of the city")
 * )
 */

class ReportReasonController extends Controller
{
    public function index()
    {
        try {
            $reasons = ReportReason::get();
            return response()->json(['reasons' => $reasons], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }


}