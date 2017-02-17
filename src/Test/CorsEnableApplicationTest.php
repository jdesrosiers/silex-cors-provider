<?php

namespace JDesrosiers\Silex\Provider\Test;

use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

class CorsEnableApplicationTest extends \PHPUnit_Framework_TestCase
{
    protected $client;

    public function setUp()
    {
        $app = new Application();
        $app["debug"] = true;
        $app->register(new CorsServiceProvider());

        $app->get("/foo", function () {
            return "foo";
        });

        $app->get("/bar", function () {
            return "bar";
        });

        $this->client = new Client($app["cors-enabled"]($app), ["HTTP_ORIGIN" => "http://www.foo.com"]);
    }

    public function testFooPreflight()
    {
        $this->client->request("OPTIONS", "/foo");
        $response = $this->client->getResponse();

        $this->assertTrue($response->isEmpty());
        $this->assertTrue($response->headers->has("Access-Control-Allow-Origin"));
    }

    public function testBarPreflight()
    {
        $this->client->request("OPTIONS", "/bar");
        $response = $this->client->getResponse();

        $this->assertTrue($response->isEmpty());
        $this->assertTrue($response->headers->has("Access-Control-Allow-Origin"));
    }

    public function testFooController()
    {
        $this->client->request("GET", "/foo");
        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertTrue($response->headers->has("Access-Control-Allow-Origin"));
    }

    public function testBarController()
    {
        $this->client->request("GET", "/bar");
        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertTrue($response->headers->has("Access-Control-Allow-Origin"));
    }
}
