<?php

namespace JDesrosiers\Tests\Silex\Provider;

use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

class CorsServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = new Application();
        $this->app->register(new CorsServiceProvider());
    }

    public function testOptionsMethod()
    {
        $this->app->get("/foo", function () {
            return "foo";
        });
        $this->app->post("/foo", function () {
            return "foo";
        });
        $this->app->flush();

        $client = new Client($this->app);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
//        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("GET,POST", $response->headers->get("Allow"));
        $this->assertEquals("", $response->getContent());
    }
}
