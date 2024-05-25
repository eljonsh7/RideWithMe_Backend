<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Car;
use Illuminate\Support\Str;

class CarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Any additional setup can be done here
    }

    public function testStoreCar()
    {
        $response = $this->postJson('/v1/cars/store', [
            'brand' => 'Toyota',
            'serie' => 'Corolla',
            'type' => 'Sedan',
            'seats_number' => 5,
            'thumbnail' => 'toyota-corolla-thumbnail.jpg',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Car created successfully',
                     'car' => [
                         'brand' => 'Toyota',
                         'serie' => 'Corolla',
                         'type' => 'Sedan',
                         'seats_number' => 5,
                         'thumbnail' => 'toyota-corolla-thumbnail.jpg',
                     ],
                 ]);

        $this->assertDatabaseHas('cars', [
            'brand' => 'Toyota',
            'serie' => 'Corolla',
            'type' => 'Sedan',
            'seats_number' => 5,
            'thumbnail' => 'toyota-corolla-thumbnail.jpg',
        ]);
    }

    public function testStoreCarDuplicate()
    {
        Car::factory()->create([
            'brand' => 'Toyota',
            'serie' => 'Corolla',
            'type' => 'Sedan',
        ]);

        $response = $this->postJson('/v1/cars/store', [
            'brand' => 'Toyota',
            'serie' => 'Corolla',
            'type' => 'Sedan',
            'seats_number' => 5,
            'thumbnail' => 'toyota-corolla-thumbnail.jpg',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'This car already exists.',
                 ]);
    }

    public function testUpdateCar()
    {
        $car = Car::factory()->create([
            'brand' => 'Toyota',
            'serie' => 'Corolla',
            'type' => 'Sedan',
            'seats_number' => 5,
            'thumbnail' => 'toyota-corolla-thumbnail.jpg',
        ]);

        $response = $this->putJson("/v1/cars/update/{$car->id}", [
            'brand' => 'Toyota',
            'serie' => 'Camry',
            'type' => 'Sedan',
            'seats_number' => 5,
            'thumbnail' => 'toyota-camry-thumbnail.jpg',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Car updated successfully',
                     'car' => [
                         'brand' => 'Toyota',
                         'serie' => 'Camry',
                         'type' => 'Sedan',
                         'seats_number' => 5,
                         'thumbnail' => 'toyota-camry-thumbnail.jpg',
                     ],
                 ]);

        $this->assertDatabaseHas('cars', [
            'brand' => 'Toyota',
            'serie' => 'Camry',
            'type' => 'Sedan',
            'seats_number' => 5,
            'thumbnail' => 'toyota-camry-thumbnail.jpg',
        ]);
    }

    public function testDeleteCar()
    {
        $car = Car::factory()->create([
            'brand' => 'Toyota',
            'serie' => 'Corolla',
            'type' => 'Sedan',
            'seats_number' => 5,
            'thumbnail' => 'toyota-corolla-thumbnail.jpg',
        ]);

        $response = $this->deleteJson("/v1/cars/delete/{$car->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Car deleted successfully.',
                 ]);

        $this->assertDatabaseMissing('cars', [
            'id' => $car->id,
        ]);
    }

    public function testDeleteCarNotFound()
    {
        $response = $this->deleteJson('/v1/cars/delete/999');

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Car not found.',
                 ]);
    }

    public function testGetAllCars()
    {
        Car::factory()->count(3)->create();

        $response = $this->getJson('/v1/cars/get');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'cars' => [
                         '*' => ['id', 'brand', 'serie', 'type', 'seats_number', 'thumbnail'],
                     ],
                 ]);
    }

    public function testGetCarById()
    {
        $car = Car::factory()->create([
            'brand' => 'Toyota',
            'serie' => 'Corolla',
            'type' => 'Sedan',
            'seats_number' => 5,
            'thumbnail' => 'toyota-corolla-thumbnail.jpg',
        ]);

        $response = $this->getJson("/v1/cars/{$car->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'brand' => 'Toyota',
                     'serie' => 'Corolla',
                     'type' => 'Sedan',
                     'seats_number' => 5,
                     'thumbnail' => 'toyota-corolla-thumbnail.jpg',
                 ]);
    }

    public function testGetCarByIdNotFound()
    {
        $response = $this->getJson('/v1/cars/999');

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Car not found.',
                 ]);
    }
}
