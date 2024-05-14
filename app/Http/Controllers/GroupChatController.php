<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Group;
use App\Models\Message;
use App\Models\Reservation;
use Exception;
use Illuminate\Http\Request;

class GroupChatController extends Controller
{

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
        $members = $reservations->pluck('user');
        $isMember = $members->contains($authUser) || $owner->id == $authUser->id;

        if (!$isMember) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }
        $formatUserData = function ($user) {
            return [
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'profile_picture' => $user->profile_picture
            ];
        };
        $formattedOwner = $formatUserData($owner);
        $formattedMembers = $members->map($formatUserData);

        return response()->json([
            'owner' => $formattedOwner,
            'members' => $formattedMembers,
        ]);
    }

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
            return response()->json([
                'message' => $message,
            ]);

        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }

    }

}
