<?php

namespace JDesrosiers\Silex\Provider\Test;

use JDesrosiers\Silex\Provider\Cors;
use Symfony\Component\HttpFoundation\Request;

class CorsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Cors
     */
    private $cors;

    public function testIsCorsRequestWhenContainsOriginHeader()
    {
        $request = new Request();
        $request->headers->set('Origin', 'http://example.com');

        $this->assertTrue($this->cors->isCorsRequest($request));
    }

    public function testIsNotCorsRequestWithoutOriginHeader()
    {
        $request = new Request();

        $this->assertFalse($this->cors->isCorsRequest($request));
    }

    public function testIsPreflightRequest()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_OPTIONS);
        $request->headers->set('Access-Control-Request-Method', Request::METHOD_POST);

        $this->assertTrue($this->cors->isPreflightRequest($request));
    }

    public function testIsNotPreflightRequestWhenNotOptionsMethod()
    {
        $request = new Request();
        $request->headers->set('Access-Control-Request-Method', Request::METHOD_POST);

        $this->assertFalse($this->cors->isPreflightRequest($request));
    }

    public function testIsNotPreflightRequestWithoutAccessControlRequestMethodHeader()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_OPTIONS);

        $this->assertFalse($this->cors->isPreflightRequest($request));
    }

    protected function setUp()
    {
        $this->cors = new Cors($this->getMock('Silex\Application'));
    }
}
