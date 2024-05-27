<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{

    public function getUserNotifications($user)
    {
        $notifications = Notification::where('user_id', $user)->get();

        return response()->json([
            'notifications' => $notifications->isEmpty() ? [] : $notifications
        ], 200);
    }

}
