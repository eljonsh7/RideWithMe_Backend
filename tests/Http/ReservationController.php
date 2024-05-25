<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(); // Seed the database
        $this->user = User::factory()->create();
        $this->actingAs($this->user); // Authenticate user for requests
    }

    /** @test */
    public function user_can_create_reservation()
    {
        $route = Route::factory()->create();

        $data = [
            'user_id' => $this->user->id,
            'route_id' => $route->id,
            'status' => 'requested',
            'seat' => 1,
        ];

        $response = $this->postJson('/v1/reservations/create', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment(['status' => 'requested']);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->user->id,
            'route_id' => $route->id,
            'status' => 'requested',
            'seat' => 1,
        ]);
    }

    /** @test */
    public function user_cannot_create_duplicate_reservation()
    {
        $route = Route::factory()->create();
        Reservation::create([
            'user_id' => $this->user->id,
            'route_id' => $route->id,
            'status' => 'requested',
            'seat' => 1,
        ]);

        $data = [
            'user_id' => $this->user->id,
            'route_id' => $route->id,
            'status' => 'requested',
            'seat' => 1,
        ];

        $response = $this->postJson('/v1/reservations/create', $data);

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Reservation already exists!']);
    }

    /** @test */
    public function user_can_update_reservation_status()
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'requested',
        ]);

        $data = [
            'status' => 'accepted',
        ];

        $response = $this->putJson("/v1/reservations/update/{$reservation->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'accepted']);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'accepted',
        ]);
    }

    /** @test */
    public function user_can_get_received_requests()
    {
        $response = $this->getJson('/v1/reservations/received');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'reservations' => [
                '*' => [
                    'id',
                    'user_id',
                    'route_id',
                    'status',
                    'seat',
                    'created_at',
                    'updated_at',
                    'route' => [
                        'id',
                        'driver_id',
                        'departure',
                        'destination',
                        'created_at',
                        'updated_at',
                    ],
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function user_can_get_sent_requests()
    {
        $response = $this->getJson('/v1/reservations/sent');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'reservations' => [
                '*' => [
                    'id',
                    'user_id',
                    'route_id',
                    'status',
                    'seat',
                    'created_at',
                    'updated_at',
                    'route' => [
                        'id',
                        'driver_id',
                        'departure',
                        'destination',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
    }
}
