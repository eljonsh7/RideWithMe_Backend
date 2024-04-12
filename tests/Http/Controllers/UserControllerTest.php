<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\UserController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class UserControllerTest extends \Tests\TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        $this->controller = $app->make(UserController::class);
    }

    public function testSignup()
    {
        $request = new Request([
            'firstName' => 'Erblin',
            'lastName' => 'Masari',
            'role' => 'Passenger',
            'email' => 'e_m@gmail.com',
            'password' => '12345678',
        ]);

        $response = $this->controller->signup($request);
        $this->assertEquals(201, $response->getStatusCode());


        $request = new Request([
            'firstName' => 'Era',
            'lastName' => 'Mazllumi',
            'role' => 'Passenger',
            'email' => 'e_m@gmail.com',
            'password' => '12345678',
        ]);

        $response = $this->controller->signup($request);

        $this->assertEquals(409, $response->getStatusCode());
    }
}
