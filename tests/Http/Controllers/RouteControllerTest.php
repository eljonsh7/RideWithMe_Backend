<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\RouteController;
use Illuminate\Http\Request;

class RouteControllerTest extends \Tests\TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        $this->controller = $app->make(RouteController::class);
    }

    public function testSignup()
    {
        $request = new Request([
            'cityFromId' => '1',
            'cityToId' => '2',
            'date' => now(),
        ]);

        $response = $this->controller->search($request);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
