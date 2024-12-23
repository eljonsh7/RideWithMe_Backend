<?php

namespace App\Http\Controllers\API\V1;

use App\Events\NotificationEvent;
use App\Http\Requests\API\V1\Friend\AcceptFriendRequestRequest;
use App\Http\Requests\API\V1\Friend\CancelFriendRequestRequest;
use App\Http\Requests\API\V1\Friend\DeclineFriendRequestRequest;
use App\Http\Requests\API\V1\Friend\GetFriendsRequest;
use App\Http\Requests\API\V1\Friend\SendFriendRequestRequest;
use App\Http\Requests\API\V1\Friend\UnfriendRequest;
use App\Models\Friend;
use App\Models\FriendRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *      schema="Friend",
 *      type="object",
 *      title="Friend",
 *      description="Friend model",
 *      required={"id", "user_id", "friend_id"},
 *      @OA\Property(property="id", format="uuid", type="string", description="Primary key of the friend"),
 *      @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the friend was created"),
 *      @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the friend was updated"),
 *      @OA\Property(property="user_id", format="uuid", type="string", description="User ID"),
 *      @OA\Property(property="friend_id", format="uuid", type="string", description="Friend ID"),
 * )
 */

class FriendController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/friends/request/{user}",
     *     summary="Send a friend request",
     *     tags={"Friends"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend request sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Friend request sent.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Friend request already sent or accepted",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Friend request already sent or accepted.")
     *         )
     *     )
     * )
     */

    public function sendFriendRequest(SendFriendRequestRequest $request, $user)
    {
        $request->validated();
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

    /**
     * @OA\Post(
     *     path="/api/v1/friends/accept/{user}",
     *     summary="Accept a friend request",
     *     tags={"Friends"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend request accepted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Friend request accepted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Friend request not found or already accepted",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Friend request not found or already accepted.")
     *         )
     *     )
     * )
     */

    public function acceptFriendRequest(AcceptFriendRequestRequest $request, $user)
    {
        $request->validated();
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

    /**
     * @OA\Delete(
     *     path="/api/v1/friends/decline/{user}",
     *     summary="Decline a friend request",
     *     tags={"Friends"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend request declined",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Friend request declined.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Friend request not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Friend request not found.")
     *         )
     *     )
     * )
     */

    public function declineFriendRequest(DeclineFriendRequestRequest $request, $user)
    {
        $request->validated();
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

    /**
     * @OA\Delete(
     *     path="/api/v1/friends/cancel/{user}",
     *     summary="Cancel a friend request",
     *     tags={"Friends"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend request canceled",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Friend request canceled.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Friend request not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Friend request not found.")
     *         )
     *     )
     * )
     */

    public function cancelFriendRequest(CancelFriendRequestRequest $request, $user)
    {
        $request->validated();
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

    /**
     * @OA\Delete(
     *     path="/api/v1/friends/unfriend/{user}",
     *     summary="Unfriend a user",
     *     tags={"Friends"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User unfriended",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You have unfriended the user.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="You are not friends with this user",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="You are not friends with this user.")
     *         )
     *     )
     * )
     */

    public function unfriend(UnfriendRequest $request, $user)
    {
        $request->validated();
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

    /**
     * @OA\Get(
     *     path="/api/v1/friends/get/{user}",
     *     summary="Get friends of a user",
     *     tags={"Friends"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of friends",
     *         @OA\JsonContent(
     *             @OA\Property(property="friends", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */

    public function getFriends(GetFriendsRequest $request,$user)
    {
        $request->validated();
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

        return response()->json(['message' => 'Friends fetched successfully', 'friends' => $friends],200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/friends/requests/get",
     *     summary="Get friend requests for the authenticated user",
     *     tags={"Friends"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Requests fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Requests fetched successfully"),
     *             @OA\Property(
     *                 property="requests",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Friend")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized.")
     *         )
     *     )
     * )
     */


    public function getFriendRequests(GetFriendsRequest $request)
    {
        $request->validated();
        $user = auth()->user();
        $requests = FriendRequest::where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->with('sender')
            ->get();

        return response()->json(['message' => 'Requests fetched successfully', 'requests' => $requests],200);
    }
}
