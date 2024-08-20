<?php

namespace Tests\Feature;

use App\Events\MessageEvent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->recipient = User::factory()->create();
    }

    /** @test */
    public function user_can_send_message()
    {
        Event::fake();

        $this->actingAs($this->user);

        $response = $this->postJson(route('chat.sendMessage', $this->recipient->id), [
            'message' => 'Hello',
            'type' => 'text',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('messages', ['content' => 'Hello']);
        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => Conversation::first()->id,
        ]);

        Event::assertDispatched(MessageEvent::class);
    }

    /** @test */
    public function user_can_get_conversation()
    {
        $this->actingAs($this->user);

        $conversation = Conversation::create([
            'sender_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'private',
        ]);

        $message = Message::create([
            'user_id' => $this->user->id,
            'content' => 'Hello',
            'type' => 'text',
        ]);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);

        $response = $this->getJson(route('chat.getConversation', [$this->recipient->id, 'private']));

        $response->assertStatus(200);
        $response->assertJsonStructure(['messages']);
    }

    /** @test */
    public function user_can_get_conversations_with_messages()
    {
        $this->actingAs($this->user);

        $conversation = Conversation::create([
            'sender_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'private',
        ]);

        $message = Message::create([
            'user_id' => $this->user->id,
            'content' => 'Hello',
            'type' => 'text',
        ]);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);

        $response = $this->getJson(route('chat.getConversationsWithMessages'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['conversations']);
    }

    /** @test */
    public function user_can_delete_conversation()
    {
        $this->actingAs($this->user);

        $conversation = Conversation::create([
            'sender_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'private',
        ]);

        $response = $this->deleteJson(route('chat.deleteConversation', $this->recipient->id));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    /** @test */
    public function user_can_mark_conversation_as_read()
    {
        $this->actingAs($this->user);

        $conversation = Conversation::create([
            'sender_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'private',
            'unread_messages' => 5,
        ]);

        $response = $this->patchJson(route('chat.markConversationAsRead', $this->recipient->id));

        $response->assertStatus(200);
        $this->assertEquals(0, $conversation->fresh()->unread_messages);
    }
}
