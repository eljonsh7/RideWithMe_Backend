<?php

namespace Tests\Feature;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase; // Refresh the database before each test

    /** @test */
    public function can_create_location()
    {
        $data = [
            'cityId' => 'your_city_id', // Replace with a valid city ID
            'name' => 'Test Location',
            'googleMapsLink' => 'https://maps.google.com/test'
        ];

        $response = $this->postJson('/v1/locations/store', $data);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Location created successfully',
            ])
            ->assertJsonStructure([
                'message',
                'location' => [
                    'id',
                    'city_id',
                    'name',
                    'google_maps_link',
                ],
            ]);

        // Assert location exists in database
        $this->assertDatabaseHas('locations', [
            'name' => 'Test Location',
        ]);
    }

    /** @test */
    public function can_update_location()
    {
        $location = Location::factory()->create();

        $data = [
            'name' => 'Updated Location Name',
            'google_maps_link' => 'https://maps.google.com/updated',
        ];

        $response = $this->putJson("/v1/locations/update/{$location->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Location updated successfully',
            ])
            ->assertJsonStructure([
                'message',
                'location' => [
                    'id',
                    'city_id',
                    'name',
                    'google_maps_link',
                ],
            ]);

        // Assert location updated in database
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Updated Location Name',
            'google_maps_link' => 'https://maps.google.com/updated',
        ]);
    }

    /** @test */
    public function can_delete_location()
    {
        $location = Location::factory()->create();

        $response = $this->delete("/v1/locations/delete/{$location->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Location deleted successfully',
            ]);

        // Assert location deleted from database
        $this->assertDeleted('locations', [
            'id' => $location->id,
        ]);
    }

    /** @test */
    public function can_get_location_by_id()
    {
        $location = Location::factory()->create();

        $response = $this->get("/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'city_id',
                    'name',
                    'google_maps_link',
                ],
            ]);
    }

    /** @test */
    public function can_get_all_locations_for_city()
    {
        Location::factory()->create(['city_id' => 'your_city_id']); // Replace with a valid city ID

        $response = $this->get("/v1/locations/get/your_city_id"); // Replace with a valid city ID

        $response->assertStatus(200)
            ->assertJsonStructure([
                'locations' => [
                    '*' => [
                        'id',
                        'city_id',
                        'name',
                        'google_maps_link',
                    ],
                ],
            ]);
    }
}
