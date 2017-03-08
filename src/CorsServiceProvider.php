<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Silex\Controller;
use Silex\ControllerCollection;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

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

        $app->on(KernelEvents::EXCEPTION, function (GetResponseForExceptionEvent $event) {
            $e = $event->getException();
            if ($e instanceof MethodNotAllowedHttpException && $e->getHeaders()["Allow"] === "OPTIONS") {
                $event->setException(new NotFoundHttpException());
            }
        });
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

        $app["cors-enabled"] = $app->protect(function ($subject) use ($app) {
            if ($subject instanceof Controller) {
                $app->match($subject->getRoute()->getPath(), new OptionsController())
                    ->after($app["cors"])
                    ->method("OPTIONS");
            } else if ($subject instanceof ControllerCollection) {
                $subject->match("{path}", new OptionsController())
                    ->assert("path", ".*")
                    ->method("OPTIONS");
            }
            $subject->after($app["cors"]);

            return $subject;
        });
    }
}
