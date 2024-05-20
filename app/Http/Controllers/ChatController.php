<?php

namespace App\Http\Controllers;

use App\Events\MessageEvent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Group;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ChatController extends Controller
{

    public function sendMessage(Request $request, $recipient)
    {
        $request->validate([
            'message' => 'required|string',
            'type' => 'required|string',
        ]);

        $authenticatedUserId = auth()->id();

        $conversation1 = Conversation::firstOrCreate(
            ['sender_id' => $authenticatedUserId, 'recipient_id' => $recipient, 'type' => 'private'],
            ['unread_messages' => 0]
        );

        $conversation2 = Conversation::firstOrCreate(
            ['sender_id' => $recipient, 'recipient_id' => $authenticatedUserId, 'type' => 'private'],
            ['unread_messages' => 0]
        );

        $message = Message::create([
            'user_id' => $authenticatedUserId,
            'content' => $request->message,
            'type' => $request->type,
        ]);

        ConversationMessage::create([
            'conversation_id' => $conversation1->id,
            'message_id' => $message->id
        ]);
        ConversationMessage::create([
            'conversation_id' => $conversation2->id,
            'message_id' => $message->id
        ]);

        // Increment unread messages for the recipient
        if ($conversation1->sender_id == $recipient) {
            $conversation1->increment('unread_messages');
        } else {
            $conversation2->increment('unread_messages');
        }

        broadcast(new MessageEvent($message,$recipient))->toOthers();
        broadcast(new MessageEvent($message, $authenticatedUserId))->toOthers();

        return response()->json([
            'message' => $message,
        ]);
    }


    public function getConversation($recipient, $type)
    {
        $user = auth()->user();
        $authenticatedUserId = $user->id;

        $conversation = Conversation::where('recipient_id', $recipient)
            ->where('type', $type)
            ->when($type == 'private', function ($query) use ($authenticatedUserId) {
                $query->where('sender_id', $authenticatedUserId);
            })
            ->when($type == 'group', function ($query) use ($authenticatedUserId, $recipient) {
                $groupObj = Group::with(['route.driver'])
                    ->find($recipient);
                if ($groupObj) {
                    // Check if the user is the driver
                    $isDriver = $groupObj->route->driver->id == $authenticatedUserId;

                    // Check if the user has a reservation in the route
                    $hasReservation = Reservation::where('route_id', $groupObj->route->id)
                        ->where('user_id', $authenticatedUserId)
                        ->exists();

                    if ($isDriver || $hasReservation) {
                        $query->where('recipient_id', $recipient);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    $query->whereRaw('1 = 0'); //No results are returned
                }
            })
            ->first();

        if (!$conversation) {
            return response()->json(['messages' => [], 'status' => 'undefined'], 200);
        }

        $messages = $conversation->messages->map(function ($message) {
            unset($message->pivot);

            $sender = User::find($message->user_id);
            $message->first_name = $sender->first_name;
            $message->last_name = $sender->last_name;
            $message->profile_picture = $sender->profile_picture;

            return $message;
        });

        return response()->json([
            'messages' => $messages,
        ], 200);
    }

    public function getConversationsWithMessages()
    {
        $user = auth()->user();

        // Retrieve conversations with last message and recipient data
        $conversations = Conversation::with(['messages' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }, 'recipient'])
            ->where('sender_id', $user->id)
            ->where('type', 'private')
            ->get();

        $conversationData = $conversations->map(function ($conversation) use ($user) {
            $lastMessage = $conversation->messages->first();
            $otherParticipant = User::find($conversation->recipient_id);

            return [
                'id' => $conversation->id,
                'sender_id' => $otherParticipant->id,
                'type' => $conversation->type,
                'unread_messages' => $conversation->unread_messages,
                'first_name' => $otherParticipant->first_name,
                'last_name' => $otherParticipant->last_name,
                'profile_picture' => $otherParticipant->profile_picture,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'content' => $lastMessage->content,
                    'created_at' => $lastMessage->created_at,
                    'type' => $lastMessage->type,
                    'user_id' => $lastMessage->user_id,
                ] : null,
            ];
        })->sortByDesc(function ($conversation) {
            return $conversation['last_message']['created_at'] ?? null;
        })->values()->all();

        return response()->json(['conversations' => $conversationData]);
    }


    public function deleteConversation($recipient)
    {
        $user = auth()->user();
        $authenticatedUserId = $user->id;

        $conversation = Conversation::where('sender_id', $authenticatedUserId)
            ->where('recipient_id', $recipient)
            ->where('type', 'private')
            ->first();

        if (!$conversation) {
            return response()->json(['messages' => []], 404);
        }

        ConversationMessage::where('conversation_id', $conversation->id)->delete();

        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }


    public function markConversationAsRead($recipient)
    {
        $authenticatedUserId = auth()->user()->id;

        $conversation = Conversation::where('sender_id', $authenticatedUserId)
            ->where('recipient_id', $recipient)
            ->first();
        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }
        $conversation->unread_messages = 0;
        $conversation->update();

        return response()->json(['message' => 'Conversations marked as read']);
    }

}
