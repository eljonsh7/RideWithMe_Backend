<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    /** @test */
    public function can_store_media_file()
    {
        Storage::fake('public'); // Use fake storage for testing

        $file = UploadedFile::fake()->create('test_file.jpg'); // Create a fake file

        $response = $this->postJson('/v1/media/store', [
            'media' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'File stored successfully',
            ]);

        $response->assertJsonStructure([
            'message',
            'file_path',
        ]);

        Storage::disk('public')->assertExists($response['file_path']); // Assert file exists in storage
    }
}
