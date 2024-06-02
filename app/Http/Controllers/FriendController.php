<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Models\Friend;
use App\Models\FriendRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class FriendController extends Controller
{

    public function sendFriendRequest($user)
    {
        $sender = auth()->user();

        // Check if a pending or accepted friend request already exists
        $existingRequest = FriendRequest::where('sender_id', $sender->id)
            ->where('receiver_id', $user)
            ->first();

        if ($existingRequest) {
            return response()->json(['error' => 'Friend request already sent or accepted.']);
        }
        // Create a new friend request
        FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $user
        ]);
        $notificationData = Notification::create([
            'user_id' => $user,
            'sender_id' => $sender->id,
            'type' => 'friendRequestSent',
        ]);

        $message = $sender->first_name . " " . $sender->last_name . " sent you a friend request.";
        $notificationEventData = [
            'id' => $notificationData->id,
            'user_id' => $notificationData->user_id,
            'sender_id' => $notificationData->sender_id,
            'message' => $message,
        ];
        broadcast(new NotificationEvent($notificationEventData))->toOthers();
        return response()->json(['message' => 'Friend request sent.']);
    }

    public function acceptFriendRequest($user)
    {
        $sender = User::find($user);
        if (!$sender) {
            return response()->json(['error' => 'User not found.'], 404);
        }
        $receiver = auth()->user();

        $friendRequest = FriendRequest::where('sender_id', $sender->id)
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
        $notificationData = Notification::create([
            'user_id' => $sender->id,
            'sender_id' => $receiver->id,
            'type' => 'friendRequestAccepted',
        ]);

        $message = $receiver->first_name . " " . $receiver->last_name . " accepted your friend request.";
        $notificationEventData = [
            'id' => $notificationData->id,
            'user_id' => $notificationData->user_id,
            'sender_id' => $notificationData->sender_id,
            'message' => $message,
        ];
        broadcast(new NotificationEvent($notificationEventData))->toOthers();
        return response()->json(['message' => 'Friend request accepted.']);
    }

    public function declineFriendRequest($user)
    {
        $receiver = auth()->user();
        $friendRequest = FriendRequest::where('sender_id', $user)
            ->where('receiver_id', $receiver->id)
            ->where('status', 'pending')
            ->first();

        if (!$friendRequest) {
            return response()->json(['error' => 'Friend request not found.'], 404);
        }
        $friendRequest->delete();

        $notificationData = Notification::create([
            'user_id' => $user,
            'sender_id' => $receiver->id,
            'type' => 'friendRequestDeclined',
        ]);

        $message = $receiver->first_name . " " . $receiver->last_name . " declined your friend request.";
        $notificationEventData = [
            'id' => $notificationData->id,
            'user_id' => $notificationData->user_id,
            'sender_id' => $notificationData->sender_id,
            'message' => $message,
        ];
        broadcast(new NotificationEvent($notificationEventData))->toOthers();

        return response()->json(['message' => 'Friend request declined.']);
    }

    public function cancelFriendRequest($user)
    {
        $sender = auth()->user();
        $friendRequest = FriendRequest::where('sender_id', $sender->id)
            ->where('receiver_id', $user)
            ->where('status', 'pending')
            ->first();

        if (!$friendRequest) {
            return response()->json(['error' => 'Friend request not found.'], 404);
        }
        $friendRequest->delete();

        return response()->json(['message' => 'Friend request canceled.']);
    }

    public function unfriend($user)
    {
        $authUser = auth()->user();
        $friend = User::find($user);

        if (!$friend) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        if (!$authUser->friends->contains($friend)) {
            return response()->json(['error' => 'You are not friends with this user.'], 400);
        }

        $authUser->friends()->detach($friend);
        $friend->friends()->detach($authUser);

        FriendRequest::where('sender_id', $authUser->id)->where('receiver_id', $friend->id)
            ->orWhere('sender_id', $friend->id)->where('receiver_id', $authUser->id)
            ->delete();

        return response()->json(['message' => 'You have unfriended the user.']);
    }

    public function getFriends(Request $request,$user)
    {
        $userObj = User::where('id', $user)->with(['friends' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->first();

        if (!$userObj) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $friends = $userObj->friends;

        $friends =$friends->take(5);

        $friends->each(function ($friend) {
            $friend->makeHidden(['password','pivot','role','is_admin']);
        });

        return response()->json(['friends' => $friends],200);
    }

    public function getFriendRequests()
    {
        $user = auth()->user();
        $requests = FriendRequest::where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->with('sender')
            ->get();

        return response()->json(['requests' => $requests],200);
    }
}
