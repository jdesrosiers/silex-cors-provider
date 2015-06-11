<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * The CORS service provider provides a `cors` service that a can be included in your project as application middleware.
 */
class CorsServiceProvider implements ServiceProviderInterface
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
    public function register(Application $app)
    {
        $app["cors.allowOrigin"] = "*"; // Defaults to all
        $app["cors.allowMethods"] = null; // Defaults to all
        $app["cors.maxAge"] = null;
        $app["cors.allowCredentials"] = null;
        $app["cors.exposeHeaders"] = null;

        $app["cors"] = $app->protect(new Cors($app));
    }

    private function determineAllowedMethods(RouteCollection $routes)
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

    private function createOptionsRoutes(Application $app, $allow)
    {
        foreach ($allow as $path => $routeDetails) {
            // Remove _method from requirements, it would cause a
            // E_USER_DEPRECATED error with Symfony Routing component 2.7+
            unset($routeDetails['requirements']['_method']);

            $app->match($path, new OptionsController($routeDetails["methods"]))
                ->setRequirements($routeDetails["requirements"])
                ->method("OPTIONS");
        }
    }
}
