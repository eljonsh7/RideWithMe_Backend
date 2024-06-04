<?php

namespace App\Http\Controllers;

use App\Models\Notification;

/**
 * @OA\Schema(
 *      schema="Notification",
 *      type="object",
 *      title="Notification",
 *      description="Notification model",
 *      required={"id", "user_id", "sender_id", "type", "created_at"},
 *      @OA\Property(property="id", type="string", format="uuid", description="Primary key of the notification"),
 *      @OA\Property(property="user_id", type="string", format="uuid", description="ID of the user who receives the notification"),
 *      @OA\Property(property="sender_id", type="string", format="uuid", description="ID of the user who sent the notification"),
 *      @OA\Property(property="type", type="string", description="Type of the notification"),
 *      @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the notification was created"),
 * )
 */

class NotificationController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/notifications/get/{user}",
     *     summary="Get user notifications",
     *     description="Retrieve all notifications for a specific user.",
     *     operationId="getUserNotifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID of the user",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             format="uuid"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Notification")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function getUserNotifications(){
        $notifications = Notification::where('user_id', auth()->user()->id)
            ->with('sender')
            ->get();

        return response()->json([
            'message' => 'Notifications fetched successfully',
            'notifications' => $notifications->isEmpty() ? [] : $notifications
        ], 200);
    }

}
