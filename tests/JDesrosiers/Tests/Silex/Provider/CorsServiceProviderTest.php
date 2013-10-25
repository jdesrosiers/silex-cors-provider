<?php

namespace JDesrosiers\Tests\Silex\Provider;

use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

class CorsServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = new Application();
        $this->app["debug"] = true;
        $this->app->register(new CorsServiceProvider(), array(
            "cors.maxAge" => 15,
            "cors.allowCredentials" => true,
        ));
        $this->app->after($this->app["cors"]);
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
//        $this->assertFalse($response->headers->has("Content-Type"));
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
//        $this->assertFalse($response->headers->has("Content-Type"));
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
//        $this->assertFalse($response->headers->has("Content-Type"));
    }

    public function testCorsPreFlight()
    {
        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = array(
            "HTTP_ORIGIN" => "www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
            "HTTP_ACCESS_CONTROL_REQUEST_HEADERS" => "content-type",
        );
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET", $response->headers->get("Allow"));
        $this->assertEquals("GET", $response->headers->get("Access-Control-Allow-Methods"));
        $this->assertEquals("www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertEquals("content-type", $response->headers->get("Access-Control-Allow-Headers"));
        $this->assertEquals("15", $response->headers->get("Access-Control-Max-Age"));
        $this->assertEquals("true", $response->headers->get("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
//        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testCorsPreFlightFail()
    {
        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = array(
            "HTTP_ORIGIN" => "www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "POST",
        );
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET", $response->headers->get("Allow"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Methods"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertFalse($response->headers->has("Access-Control-Max-Age"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
//        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function dataProviderAllowOrigin()
    {
        return array(
            array("www.foo.com"),
            array("www.foo.com www.bar.com"),
            array("www.bar.com www.foo.com"),
        );
    }

    /**
     * @dataProvider dataProviderAllowOrigin
     */
    public function testAllowOrigin($domain)
    {
        $this->app["cors.allowOrigin"] = $domain;

        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = array(
            "HTTP_ORIGIN" => "www.foo.com",
        );
        $client = new Client($this->app, $headers);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertFalse($response->headers->has("Access-Control-Allow-Methods"));
        $this->assertEquals("www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertFalse($response->headers->has("Access-Control-Max-Age"));
        $this->assertEquals("true", $response->headers->get("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
        $this->assertEquals("foo", $response->getContent());
    }

    public function testAllowOriginFail()
    {
        $this->app["cors.allowOrigin"] = "www.bar.com";

        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = array(
            "HTTP_ORIGIN" => "www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
        );
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET", $response->headers->get("Allow"));
        $this->assertEquals("GET", $response->headers->get("Access-Control-Allow-Methods"));
        $this->assertEquals("null", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertEquals("15", $response->headers->get("Access-Control-Max-Age"));
        $this->assertEquals("true", $response->headers->get("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
//        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testAllowMethods()
    {
        $this->app["cors.allowMethods"] = "GET";

        $this->app->match("/foo", function () {
            return "foo";
        })->method("GET|POST");

        $headers = array(
            "HTTP_ORIGIN" => "www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
        );
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET,POST", $response->headers->get("Allow"));
        $this->assertEquals("GET", $response->headers->get("Access-Control-Allow-Methods"));
        $this->assertEquals("www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertEquals("15", $response->headers->get("Access-Control-Max-Age"));
        $this->assertEquals("true", $response->headers->get("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
//        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testAllowMethodsFail()
    {
        $this->app["cors.allowMethods"] = "GET";

        $this->app->match("/foo", function () {
            return "foo";
        })->method("GET|POST");

        $headers = array(
            "HTTP_ORIGIN" => "www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "POST",
        );
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();
        print_r((string) $response);

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET,POST", $response->headers->get("Allow"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Methods"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertFalse($response->headers->has("Access-Control-Max-Age"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
//        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }
}
