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

/**
 * @OA\Schema(
 *   schema="Message",
 *   type="object",
 *   required={"content", "type", "user_id"},
 *   @OA\Property(property="id", type="string", format="uuid", description="Primary key of the message"),
 *   @OA\Property(property="content", type="string", description="Content of the message"),
 *   @OA\Property(property="type", type="string", description="Type of the message"),
 *   @OA\Property(property="user_id", type="string", format="uuid", description="User unique identifier"),
 *   @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the message was created"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the message was updated"),
 * )
 * 
 *
 *
 * @OA\Schema(
 *   schema="Conversation",
 *   type="object",
 *   required={"id", "sender_id", "recipient_id", "type", "unread_messages"},
 *   @OA\Property(property="id", type="string", format="uuid", description="Primary key of the conversation"),
 *   @OA\Property(property="sender_id", type="string", format="uuid", description="Sender unique identifier"),
 *   @OA\Property(property="recipient_id", type="string", format="uuid", description="Recipient unique identifier"),
 *   @OA\Property(property="type", type="string", description="Type of conversation (e.g., 'private', 'group')"),
 *   @OA\Property(property="unread_messages", type="integer", description="Number of unread messages in the conversation"),
 *   @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the conversation was created"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the conversation was updated"),
 * )
 * */

class ChatController extends Controller
{

    /**
     * @OA\Post(
     *   path="/api/v1/messages/send/{recipient}",
     *   summary="Send a message",
     *   tags={"Chat"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="recipient",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"message", "type"},
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="type", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Message sent successfully", @OA\JsonContent(ref="#/components/schemas/Message")),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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

        broadcast(new MessageEvent($message, $recipient, $authenticatedUserId, 'private'))->toOthers();
        broadcast(new MessageEvent($message, $authenticatedUserId, $recipient, 'private'))->toOthers();

        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/messages/get/{recipient}/{type}",
     *   summary="Get a conversation",
     *   security={{"bearerAuth": {}}},
     *   tags={"Chat"},
     *   @OA\Parameter(
     *     name="recipient",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Parameter(
     *     name="type",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="Conversation fetched successfully", @OA\JsonContent(ref="#/components/schemas/Conversation")),
     *   @OA\Response(response=404, description="Conversation not found"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

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

        $messages = $conversation->messages->sortBy('created_at')->map(function ($message) {
            unset($message->pivot);

            $sender = User::find($message->user_id);
            unset($sender->password); // Ensure the sender's password is unset
            $message->sender = $sender;

            return $message;
        })->values()->toArray();

        return response()->json([
            'messages' => $messages,
        ], 200);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/messages/get/last",
     *   summary="Get conversations with messages",
     *   tags={"Chat"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Response(response=200, description="Conversations fetched successfully", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Conversation"))),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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
            unset($otherParticipant->password);

            return [
                'id' => $conversation->id,
                'sender' => $otherParticipant,
                'type' => $conversation->type,
                'unread_messages' => $conversation->unread_messages,
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

        return response()->json(['message' => 'Conversations fetched successfully', 'conversations' => $conversationData]);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/messages/delete/{recipient}",
     *   summary="Delete a conversation",
     *   tags={"Chat"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="recipient",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(response=200, description="Conversation deleted successfully"),
     *   @OA\Response(response=404, description="Conversation not found"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

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

    /**
     * @OA\Put(
     *   path="/api/v1/messages/read/{recipient}",
     *   summary="Mark a conversation as read",
     *   tags={"Chat"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="recipient",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(response=200, description="Conversation marked as read"),
     *   @OA\Response(response=404, description="Conversation not found"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */

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
