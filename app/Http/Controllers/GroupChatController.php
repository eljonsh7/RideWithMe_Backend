<?php

namespace App\Http\Controllers;

use App\Events\MessageEvent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Group;
use App\Models\Message;
use App\Models\Reservation;
use Exception;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *      schema="Group",
 *      type="object",
 *      title="Group",
 *      description="Group model",
 *      required={"id", "route_id", "status"},
 *      @OA\Property(property="id", type="string", format="uuid", description="Primary key of the group"),
 *      @OA\Property(property="route_id", type="string", format="uuid", description="ID of the associated route"),
 *      @OA\Property(property="group_picture", type="string", description="URL of the group picture", nullable=true),
 *      @OA\Property(property="status", type="string", description="Status of the group"),
 * )
 */

class GroupChatController extends Controller
{

    /**
 * @OA\Post(
 *     path="/api/v1/messages/group/store",
 *     tags={"Group Chat"},
 *     security={{"bearerAuth": {}}},
 *     summary="Create a new group",
 *     description="Creates a new group and initializes a conversation for the group.",
 *     operationId="storeGroup",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"route_id"},
 *             @OA\Property(property="route_id", type="string", description="ID of the route"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Group created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Group created successfully"),
 *             @OA\Property(property="group_details", type="object", ref="#/components/schemas/Group"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(property="errors", type="object"),
 *         ),
 *     ),
 * )
 */
    public function store(Request $request)
    {
        $request->validate([
            'route_id' => 'required|string'
        ]);
        $owner = auth()->user();
        $group = Group::create([
            'route_id' => $request->route_id
        ]);

        Conversation::create([
            'sender_id' => $owner->id,
            'recipient_id' => $group->id,
            'unread_messages' => 0,
            'type' => 'group'
        ]);

        return response()->json(['message' => 'Group created successfully', 'group_details' => $group], 201);
    }

    /**
 * @OA\Get(
 *     path="/api/v1/members/get/{group}",
 *     tags={"Group Chat"},
 *     security={{"bearerAuth": {}}},
 *     summary="Retrieve all group members",
 *     description="Retrieves all members of the specified group.",
 *     operationId="retrieveAllGroupMembers",
 *     @OA\Parameter(
 *         name="group",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="ID of the group"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Group members retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="owner", type="object", ref="#/components/schemas/User"),
 *             @OA\Property(property="members", type="array",
 *                 @OA\Items(ref="#/components/schemas/User")
 *             ),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="User is not a member of the group",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not a member of this group"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Group not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Group not found."),
 *         ),
 *     ),
 * )
 */

    public function retrieveAllGroupMembers($group)
    {
        $groupObj = Group::where('id', $group)->first();
        if (!$groupObj) {
            return response()->json(['message' => 'Group not found.'], 404);
        }
        $authUser = auth()->user();
        $owner = $groupObj->route->driver;
        $reservations = Reservation::where('route_id', $groupObj->route_id)
            ->where('status', 'accepted')
            ->with('user')
            ->get();
        $members = collect($reservations->pluck('user')->toArray());

        $memberIds = collect($members)->pluck('id')->toArray();

//        dd($members);
        $isMember = in_array($authUser->id, $memberIds) || $owner->id == $authUser->id;

        if (!$isMember) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }
        $formatUserData = function ($user) {
            return [
                'user_id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'profile_picture' => $user['profile_picture']
            ];
        };
        $formattedOwner = $formatUserData($owner);
        $formattedMembers = $members->map($formatUserData);


        return response()->json([
            'message' => 'Members fetched successfully',
            'owner' => $formattedOwner,
            'members' => $formattedMembers,
        ]);
    }

    /**
 * @OA\Post(
 *     path="api/v1/messages/group/send/{group}",
 *     tags={"Group Chat"},
 *     security={{"bearerAuth": {}}},
 *     summary="Send a message to a group",
 *     description="Sends a message to the specified group.",
 *     operationId="sendMessageToGroup",
 *     @OA\Parameter(
 *         name="group",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="ID of the group"
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"message", "type"},
 *             @OA\Property(property="message", type="string", description="Message content"),
 *             @OA\Property(property="type", type="string", description="Type of the message"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Message sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="object", ref="#/components/schemas/Message"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="User is not a member of the group",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="You are not a member of this group"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="An error occurred",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred."),
 *             @OA\Property(property="error", type="string"),
 *         ),
 *     ),
 * )
 */
    public function sendMessageToGroup(Request $request, $group)
    {
        $request->validate([
            'message' => 'required|string',
            'type' => 'required|string',
        ]);
        $user = auth()->user();
        $authenticatedUserId = $user->id;
        try {
            $groupObj = Group::where('id', $group)
                ->with('route')
                ->with('route.driver')
                ->first();

            $groupMember = Reservation::where('route_id', $groupObj->route_id)
                ->where('status', 'accepted')
                ->where('user_id', $authenticatedUserId)
                ->first();
            $isDriver = $groupObj->route->driver->id != $authenticatedUserId;
            if (!$groupMember && $isDriver) {
                return response()->json(['message' => 'You are not a member of this group'], 403);
            }
            $conversationOfOwner = Conversation::where('sender_id', $groupObj->route->driver->id)
                ->where('recipient_id', $group)
                ->where('type', 'group')
                ->first();
            $message = Message::create([
                'user_id' => $authenticatedUserId,
                'content' => $request->message,
                'type' => $request->type,
            ]);
            ConversationMessage::create([
                'conversation_id' => $conversationOfOwner->id,
                'message_id' => $message->id
            ]);

            $groupMembers = Reservation::where('route_id', $groupObj->route_id)
                ->where('status', 'accepted')
                ->get();

            unset($user->password);

            broadcast(new MessageEvent($message,$groupObj->route->driver->id,$user, 'group'))->toOthers();
            foreach ($groupMembers as $groupMember1){
                broadcast(new MessageEvent($message, $groupMember1->user_id,$user, 'group'))->toOthers();
            }

            return response()->json([
                'message' => $message,
            ]);

        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }

    }

}
