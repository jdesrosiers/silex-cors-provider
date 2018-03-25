<?php

namespace JDesrosiers\Silex\Provider\Test;

use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

class CorsServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = new Application();
        $this->app["debug"] = true;
        $this->app->register(new CorsServiceProvider(), [
            "cors.maxAge" => 15,
        ]);
    }

    public function testCorsPreFlight()
    {
        $this->app["cors-enabled"]($this->app);

        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = [
            "HTTP_ORIGIN" => "http://www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
            "HTTP_ACCESS_CONTROL_REQUEST_HEADERS" => "content-type",
        ];
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET", $response->headers->get("Allow"));
        $this->assertEquals("GET", $response->headers->get("Access-Control-Allow-Methods"));
        $this->assertEquals("http://www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertEquals("content-type", $response->headers->get("Access-Control-Allow-Headers"));
        $this->assertEquals("15", $response->headers->get("Access-Control-Max-Age"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testCorsPreFlightFail()
    {
        $this->app["cors-enabled"]($this->app);

        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = [
            "HTTP_ORIGIN" => "http://www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "POST",
        ];
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
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function dataProviderAllowOrigin()
    {
        return [
            ["*"],
            ["http://www.foo.com"],
            ["*.foo.com"],
            ["http://*.foo.com"],
            ["http://www.foo.com http://www.bar.com"],
            ["*.foo.com http://www.bar.com"],
            ["http://www.bar.com http://www.foo.com"],
        ];
    }

    /**
     * @dataProvider dataProviderAllowOrigin
     */
    public function testAllowOrigin($domain)
    {
        $this->app["cors-enabled"]($this->app, ["allowOrigin" => $domain]);

        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = [
            "HTTP_ORIGIN" => "http://www.foo.com",
        ];
        $client = new Client($this->app, $headers);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertFalse($response->headers->has("Access-Control-Allow-Methods"));
        $this->assertEquals("http://www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertFalse($response->headers->has("Access-Control-Max-Age"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
        $this->assertEquals("foo", $response->getContent());
    }

    public function dataProviderAllowOriginFail()
    {
        return [
            ["http://foo.example.com"],
            ["http://bar.foo.example.com"],
            ["http://bar.www.foo.example.com"],
            ["*w.foo.example.com"],
            ["w*.foo.example.com"],
            ["www.*.example.com"],
            ["http://*w.foo.example.com"],
            ["http://w*.foo.example.com"],
            ["http://www.*.example.com"]
        ];
    }

    /**
     * @dataProvider dataProviderAllowOriginFail
     */
    public function testAllowOriginFail($domain)
    {
        $this->app["cors-enabled"]($this->app, ["allowOrigin" => $domain]);

        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = [
            "HTTP_ORIGIN" => "http://www.foo.example.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
        ];
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
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testDefaultAllowMethodsWithMultipleAllow()
    {
        $this->app["cors-enabled"]($this->app);

        $this->app->match("/foo", function () {
            return "foo";
        })->method("GET|POST");

        $headers = array(
            "HTTP_ORIGIN" => "http://www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
        );
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET,POST", $response->headers->get("Allow"));
        $this->assertEquals("GET", $response->headers->get("Access-Control-Allow-Methods"));
        $this->assertEquals("http://www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertEquals("15", $response->headers->get("Access-Control-Max-Age"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testAllowMethods()
    {
        $this->app["cors-enabled"]($this->app, ["allowMethods" => "GET"]);

        $this->app->match("/foo", function () {
            return "foo";
        })->method("GET|POST");

        $headers = [
            "HTTP_ORIGIN" => "http://www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
        ];
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET,POST", $response->headers->get("Allow"));
        $this->assertEquals("GET", $response->headers->get("Access-Control-Allow-Methods"));
        $this->assertEquals("http://www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertEquals("15", $response->headers->get("Access-Control-Max-Age"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testAllowHeadersFail()
    {
        $this->app["cors-enabled"]($this->app, ["allowHeaders" => ""]);

        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = [
            "HTTP_ORIGIN" => "http://www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
            "HTTP_ACCESS_CONTROL_REQUEST_HEADERS" => "if-modified-since",
        ];
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
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testAllowMethodsFail()
    {
        $this->app["cors-enabled"]($this->app, ["allowMethods" => "GET"]);

        $this->app->match("/foo", function () {
            return "foo";
        })->method("GET|POST");

        $headers = [
            "HTTP_ORIGIN" => "http://www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "POST",
        ];
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET,POST", $response->headers->get("Allow"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Methods"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertFalse($response->headers->has("Access-Control-Max-Age"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testMultipleAllowMethods()
    {
        $this->app["cors-enabled"]($this->app, ["allowMethods" => "GET,POST"]);

        $this->app->match("/foo", function () {
            return "foo";
        })->method("GET|POST|DELETE");

        $headers = array(
            "HTTP_ORIGIN" => "http://www.foo.com",
            "HTTP_ACCESS_CONTROL_REQUEST_METHOD" => "GET",
        );
        $client = new Client($this->app, $headers);
        $client->request("OPTIONS", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("204", $response->getStatusCode());
        $this->assertEquals("GET,POST,DELETE", $response->headers->get("Allow"));
        $this->assertEquals("GET", $response->headers->get("Access-Control-Allow-Methods"));
        $this->assertEquals("http://www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertEquals("15", $response->headers->get("Access-Control-Max-Age"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Credentials"));
        $this->assertFalse($response->headers->has("Access-Control-Expose-Headers"));
        $this->assertFalse($response->headers->has("Content-Type"));
        $this->assertEquals("", $response->getContent());
    }

    public function testAllowCredentialsAndExposeHeaders()
    {
        $this->app["cors-enabled"]($this->app, ["allowCredentials" => true, "exposeHeaders" => "Foo-Bar,Baz"]);

        $this->app->get("/foo", function () {
            return "foo";
        });

        $headers = [
            "HTTP_ORIGIN" => "http://www.foo.com",
        ];
        $client = new Client($this->app, $headers);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("200", $response->getStatusCode());
        $this->assertFalse($response->headers->has("Access-Control-Allow-Methods"));
        $this->assertEquals("http://www.foo.com", $response->headers->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers->has("Access-Control-Allow-Headers"));
        $this->assertFalse($response->headers->has("Access-Control-Max-Age"));
        $this->assertEquals("true", $response->headers->get("Access-Control-Allow-Credentials"));
        $this->assertEquals("Foo-Bar,Baz", $response->headers->get("Access-Control-Expose-Headers"));
        $this->assertEquals("foo", $response->getContent());
    }

    public function testNotEnabledMethod()
    {
        $this->app["cors-enabled"]($this->app);

        $this->app->post("/foo", function () {
            return "foo";
        });

        $client = new Client($this->app);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("405", $response->getStatusCode());
        $this->assertEquals("OPTIONS, POST", $response->headers->get("Allow"));
    }

    public function testRouteWithOnlyOptionsRespondsWith404()
    {
        $this->app["cors-enabled"]($this->app);

        $client = new Client($this->app);
        $client->request("GET", "/foo");

        $response = $client->getResponse();

        $this->assertEquals("404", $response->getStatusCode());
    }
}
