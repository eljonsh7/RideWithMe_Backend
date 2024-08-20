<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\Request;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->recipient = User::factory()->create();
    }

    /** @test */
    public function user_can_send_friend_request()
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('friend.sendFriendRequest', $this->recipient->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Friend request sent.']);
        $this->assertDatabaseHas('requests', [
            'sender_id' => $this->user->id,
            'receiver_id' => $this->recipient->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function user_cannot_send_duplicate_friend_request()
    {
        $this->actingAs($this->user);

        // Send the first request
        $this->postJson(route('friend.sendFriendRequest', $this->recipient->id));

        // Try sending another request
        $response = $this->postJson(route('friend.sendFriendRequest', $this->recipient->id));

        $response->assertStatus(200);
        $response->assertJson(['error' => 'Friend request already sent or accepted.']);
    }

    /** @test */
    public function user_can_accept_friend_request()
    {
        $this->actingAs($this->recipient);

        // Create a friend request
        Request::create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->recipient->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('friend.acceptFriendRequest', $this->user->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Friend request accepted.']);
        $this->assertDatabaseHas('friends', [
            'user_id' => $this->user->id,
            'friend_id' => $this->recipient->id,
        ]);
    }

    /** @test */
    public function user_cannot_accept_nonexistent_or_already_accepted_friend_request()
    {
        $this->actingAs($this->recipient);

        // Attempt to accept a non-existent friend request
        $response = $this->postJson(route('friend.acceptFriendRequest', $this->user->id));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Friend request not found or already accepted.']);
    }

    /** @test */
    public function user_can_decline_friend_request()
    {
        $this->actingAs($this->recipient);

        // Create a friend request
        Request::create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->recipient->id,
            'status' => 'pending',
        ]);

        $response = $this->deleteJson(route('friend.declineFriendRequest', $this->user->id));

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Friend request declined.']);
        $this->assertDatabaseMissing('requests', [
            'sender_id' => $this->user->id,
            'receiver_id' => $this->recipient->id,
        ]);
    }

    /** @test */
    public function user_cannot_decline_nonexistent_friend_request()
    {
        $this->actingAs($this->recipient);

        // Attempt to decline a non-existent friend request
        $response = $this->deleteJson(route('friend.declineFriendRequest', $this->user->id));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Friend request not found.']);
    }
}
