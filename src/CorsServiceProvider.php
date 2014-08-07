<?php

namespace JDesrosiers\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;

/**
 * The CORS service provider provides a `cors` service that a can be included in your project as application middleware.
 */
class CorsServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * Add OPTIONS method support for all routes
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        $app->flush(); // This seems to be necessary sometimes.  I'm not sure why.
        $this->createOptionsRoutes($app, $this->determineAllowedMethods($app["routes"]));
    }

    /**
     * Register the cors function and set defaults
     *
     * @param Application $app
     */
    public function register(Container $app)
    {
        $app["cors.allowOrigin"] = "*"; // Defaults to all
        $app["cors.allowMethods"] = null; // Defaults to all
        $app["cors.maxAge"] = null;
        $app["cors.allowCredentials"] = false;
        $app["cors.exposeHeaders"] = null;

        $cors = new Cors();

        $app["cors"] = $app->protect(
            function (Request $request, Response $response) use ($cors, $app) {
                $response->headers->add($cors->handle($app, $request, $response));
            }
        );
    }

    protected function determineAllowedMethods(RouteCollection $routes)
    {
        $allow = array();
        foreach ($routes as $route) {
            $path = $route->getPath();
            if (!array_key_exists($path, $allow)) {
                $allow[$path] = array("methods" => array(), "requirements" => array());
            }

            $allow[$path]["methods"] = array_merge($allow[$path]["methods"], $route->getMethods());
            $allow[$path]["requirements"] = array_merge($allow[$path]["requirements"], $route->getRequirements());
        }

        return $allow;
    }

    protected function createOptionsRoutes(Application $app, $allow)
    {
        foreach ($allow as $path => $routeDetails) {
            $methods = $routeDetails["methods"];
            $controller = $app->match(
                $path,
                function () use ($methods) {
                    return new Response("", 204, array("Allow" => implode(",", $methods)));
                }
            );

            $controller->setRequirements($routeDetails["requirements"]);
            $controller->method("OPTIONS");
        }
    }
}
