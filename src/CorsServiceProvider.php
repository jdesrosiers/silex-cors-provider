<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

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
        $app->match("{route}", new OptionsController())
            ->assert("route", ".+")
            ->method("OPTIONS");
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
}
