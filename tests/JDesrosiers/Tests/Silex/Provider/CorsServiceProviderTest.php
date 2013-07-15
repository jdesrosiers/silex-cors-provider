<?php

namespace JDesrosiers\Tests\Silex\Provider;

use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

class CorsServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function test_()
    {
        $app = new Application();
        $app->register(new CorsServiceProvider(), array());

        $client = new Client($app);
//        $client->request("GET", "");
//        $response = $client->getResponse();
//        $this->assertEquals(200, $response->getStatusCode());
    }
}
