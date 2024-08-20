<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\Route;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GroupChatControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_create_group_and_conversation()
    {
        $user = User::factory()->create();
        $route = Route::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/v1/group-chat/store', [
            'route_id' => $route->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Group created successfully',
            ])
            ->assertJsonStructure([
                'message',
                'group_details' => [
                    'id',
                    'route_id',
                    // Add other expected fields here
                ],
            ]);

        // Assert group and conversation are created in the database
        $this->assertDatabaseHas('groups', [
            'route_id' => $route->id,
        ]);

        $this->assertDatabaseHas('conversations', [
            'sender_id' => $user->id,
            'recipient_id' => $response['group_details']['id'],
            'type' => 'group',
        ]);
    }

    /** @test */
    public function can_retrieve_all_group_members()
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $route = Route::factory()->create();
        $group = Group::factory()->create(['route_id' => $route->id]);

        Reservation::factory()->create([
            'route_id' => $route->id,
            'user_id' => $user->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($user);

        $response = $this->get("/v1/group-chat/members/{$group->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'owner' => [
                    'user_id',
                    'first_name',
                    'last_name',
                    'profile_picture',
                ],
                'members' => [
                    '*' => [
                        'user_id',
                        'first_name',
                        'last_name',
                        'profile_picture',
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_send_message_to_group()
    {
        $driver = User::factory()->create();
        $user = User::factory()->create();
        $route = Route::factory()->create(['driver_id' => $driver->id]);
        $group = Group::factory()->create(['route_id' => $route->id]);

        Reservation::factory()->create([
            'route_id' => $route->id,
            'user_id' => $user->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($user);

        $response = $this->postJson("/v1/group-chat/send-message/{$group->id}", [
            'message' => 'Hello Group!',
            'type' => 'text',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message' => [
                    'id',
                    'user_id',
                    'content',
                    'type',
                ],
            ]);

        
        $this->assertDatabaseHas('messages', [
            'content' => 'Hello Group!',
            'type' => 'text',
        ]);
    }
}
