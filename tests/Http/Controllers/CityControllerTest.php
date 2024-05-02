<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\CityController;
use Illuminate\Http\Request;

class CityControllerTest extends \Tests\TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        $this->controller = $app->make(CityController::class);
    }

    public function testAddAndGetCities()
    {
        $request = new Request([
            'name' => 'Drenas',
            'country' => 'Kosova'
        ]);

        $response = $this->controller->storeCity($request);
        $this->assertEquals(201, $response->getStatusCode());

        $request = new Request([
            'name' => 'Drenas1',
            'country' => 'Kosova'
        ]);

        $response = $this->controller->updateCity($request, "1");
        $this->assertEquals(404, $response->getStatusCode());


        $response = $this->controller->getAllCities();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
