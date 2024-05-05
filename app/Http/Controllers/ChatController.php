<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Message;
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

        $user = auth()->user();
        $authenticatedUserId = $user->id;

        $conversation1 = Conversation::where('sender_id', $authenticatedUserId)
            ->where('recipient_id', $recipient)
            ->where('type', 'private')
            ->first();

        if (!$conversation1) {
            $conversation1 = new Conversation([
                'id' => Str::uuid(),
                'sender_id' => $authenticatedUserId,
                'recipient_id' => $recipient,
                'type' => 'private',
                'unread_messages' => 0
            ]);
            $conversation1->save();
        }

        $conversation2 = Conversation::where('sender_id', $recipient)
            ->where('recipient_id', $authenticatedUserId)
            ->where('type', 'private')
            ->first();

        if (!$conversation2) {
            $conversation2 = new Conversation([
                'id' => Str::uuid(),
                'sender_id' => $recipient,
                'recipient_id' => $authenticatedUserId,
                'type' => 'private',
                'unread_messages' => 0
            ]);
            $conversation2->save();
        }

        $message = new Message([
            'id' => Str::uuid(),
            'user_id' => $authenticatedUserId,
            'content' => $request->message,
            'type' => $request->type,
        ]);
        $message->save();

        ConversationMessage::create([
            'id' => Str::uuid(),
            'conversation_id' => $conversation1->id,
            'message_id' => $message->id
        ]);

        ConversationMessage::create([
            'id' => Str::uuid(),
            'conversation_id' => $conversation2->id,
            'message_id' => $message->id
        ]);

        if ($conversation1->sender_id == $recipient) {
            $conversation1->increment('unread_messages');
        } elseif ($conversation2->sender_id == $recipient) {
            $conversation2->increment('unread_messages');
        }

        return response()->json([
            'message' => $message,
        ]);
    }



    public function getConversation($recipient, $type)
    {
        $user = auth()->user();
        $authenticatedUserId = $user->id;

        $conversation = Conversation::where(function ($query) use ($type, $authenticatedUserId, $recipient) {
            $query->where('sender_id', $authenticatedUserId)
                ->where('recipient_id', $recipient)
                ->where('type', $type);
        })->first();

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
        $conversationData = [];

        $conversations = Conversation::where(function ($query) use ($user) {
            $query->where('sender_id', $user->id);
        })->get();

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages()->orderBy('created_at', 'desc')->get();

            $otherParticipantId = ($conversation->sender_id === $user->id) ? $conversation->recipient_id : $conversation->sender_id;

            if ($conversation->type == 'group') {
//                $otherParticipant = Group::find($otherParticipantId);
            } else {
                $otherParticipant = User::find($otherParticipantId);
            }

            if ($messages->isEmpty()) {
                $lastMessage = null;
            } else {
                $lastMessage = $messages->first();
            }


            $conversationDetails = [
                'id' => $conversation->id,
                'sender_id' => $otherParticipant->id,
                'type' => $conversation->type,
                'unread_messages' => $conversation->unread_messages,
                'last_message' => ($lastMessage) ? [
                    'id' => $lastMessage->id,
                    'content' => $lastMessage->content,
                    'created_at' => $lastMessage->created_at,
                    'is_read' => $lastMessage->is_read,
                    'type' => $lastMessage->type,
                ] : null,
            ];

            if ($conversation->type == 'group') {
//                $conversationDetails['fullname'] = $otherParticipant->group_name;
//                $conversationDetails['profile_picture'] = $otherParticipant->group_picture;
            } else {
                $conversationDetails['first_name'] = $otherParticipant->first_name;
                $conversationDetails['last_name'] = $otherParticipant->last_name;
                $conversationDetails['profile_picture'] = $otherParticipant->profile_picture;
            }

            $conversationData[] = $conversationDetails;
        }

        usort($conversationData, function ($a, $b) {
            if (!$a['last_message'] && !$b['last_message']) {
                return 0; // Both conversations have no messages, consider them equal
            } elseif (!$a['last_message']) {
                return 1; // Conversation B has a message, but A doesn't, so B comes first
            } elseif (!$b['last_message']) {
                return -1; // Conversation A has a message, but B doesn't, so A comes first
            }

            return strtotime($b['last_message']['created_at']) - strtotime($a['last_message']['created_at']);
        });

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
