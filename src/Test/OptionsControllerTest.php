<?php

namespace JDesrosiers\Silex\Provider\Test;

use JDesrosiers\Silex\Provider\OptionsController;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

class OptionsControllerTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = new Application();
        $this->app["debug"] = true;
        
        $this->app->options("{path}", new OptionsController())
            ->assert("path", ".*");
    }

    public function testOptionsMethod()
    {
        $this->app->get("/foo", function () {
            return "foo";
        });
        $this->app->post("/foo", function () {
            return "foo";
        });

        $client = new Client($this->app);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("GET,POST", $response->headers->get("Allow"));
        $this->assertEquals("", $response->getContent());
    }

    public function testOptionsMethodWithRequirements()
    {
        $this->app->get("/foo/{foo}", function () {
            return "foo";
        })->assert("foo", "\d+");

        $client = new Client($this->app);
        $client->request("OPTIONS", "/foo/23");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("GET", $response->headers->get("Allow"));
        $this->assertEquals("", $response->getContent());
    }

    public function testOptionsMethodWithRequirements404()
    {
        $this->app->get("/foo/{foo}", function () {
            return "foo";
        })->assert("foo", "\d+");

        $client = new Client($this->app);
        $client->request("OPTIONS", "/foo/asdf");

        $response = $client->getResponse();

        $this->assertEquals("404", $response->getStatusCode());
    }
}
