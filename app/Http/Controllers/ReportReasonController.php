<?php

namespace App\Http\Controllers;

use App\Models\ReportReason;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *      schema="ReportReason",
 *      type="object",
 *      title="ReportReason",
 *      description="ReportReason model",
 *      required={"id", "reason"},
 *      @OA\Property(property="id", type="string", format="uuid", description="Primary key of the report reason"),
 *      @OA\Property(property="reason", type="string", description="Description of the report reason")
 * )
 */

class ReportReasonController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/report/reasons/get",
     *     summary="Get all report reasons",
     *     description="Retrieve a list of all report reasons.",
     *     operationId="getReportReasons",
     *     tags={"ReportReasons"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/ReportReason")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
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