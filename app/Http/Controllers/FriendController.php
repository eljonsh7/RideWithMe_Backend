<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\Request;
use App\Models\User;

class FriendController extends Controller
{

    public function sendFriendRequest($user)
    {
        $sender = auth()->user();

        // Check if a pending or accepted friend request already exists
        $existingRequest = Request::where('sender_id', $sender->id)
            ->where('receiver_id', $user)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingRequest) {
            return response()->json(['error' => 'Friend request already sent or accepted.']);
        }
        // Create a new friend request
        Request::create([
            'sender_id' => $sender->id,
            'receiver_id' => $user
        ]);
//        broadcast(new NotificationEvent($notificationEventData))->toOthers();
        return response()->json(['message' => 'Friend request sent.']);
    }

    public function acceptFriendRequest($user)
    {
        $sender = User::find($user);
        if (!$sender) {
            return response()->json(['error' => 'User not found.'], 404);
        }
        $receiver = auth()->user();

        $friendRequest = Request::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->first();

        if (!$friendRequest || $friendRequest->status !== 'pending') {
            return response()->json(['error' => 'Friend request not found or already accepted.'], 404);
        }

        $friendRequest->status = 'accepted';
        $friendRequest->save();

        Friend::create([
            'user_id' => $sender->id,
            'friend_id' => $receiver->id
        ]);
        Friend::create([
            'user_id' => $receiver->id,
            'friend_id' => $sender->id
        ]);
//        broadcast(new NotificationEvent($notificationEventData))->toOthers();
        return response()->json(['message' => 'Friend request accepted.']);
    }

    public function declineFriendRequest($user)
    {
        $receiver = auth()->user();
        $friendRequest = Request::where('sender_id', $user)
            ->where('receiver_id', $receiver->id)
            ->first();

        if (!$friendRequest) {
            return response()->json(['error' => 'Friend request not found.'], 404);
        }
        $friendRequest->delete();

        return response()->json(['message' => 'Friend request declined.']);
    }
}
