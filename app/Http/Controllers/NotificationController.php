<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{

    public function getUserNotifications()
    {
        $notifications = Notification::where('user_id', auth()->user()->id)
            ->with('sender')
            ->get();

        return response()->json([
            'message' => 'Notifications fetched successfully',
            'notifications' => $notifications->isEmpty() ? [] : $notifications
        ], 200);
    }

}
