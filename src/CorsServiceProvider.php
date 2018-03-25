<?php

namespace JDesrosiers\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Silex\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

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
        $app->on(KernelEvents::EXCEPTION, function (GetResponseForExceptionEvent $event) {
            $e = $event->getException();
            if ($e instanceof MethodNotAllowedHttpException && $e->getHeaders()["Allow"] === "OPTIONS") {
                $event->setException(new NotFoundHttpException("No route found for \"{$event->getRequest()->getMethod()} {$event->getRequest()->getPathInfo()}\""));
            }
        });
    }

    /**
     * Register the cors function and set defaults
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app["cors.allowOrigin"] = "*"; // Defaults to all
        $app["cors.allowMethods"] = null; // Defaults to all
        $app["cors.allowHeaders"] = null; // Defaults to all
        $app["cors.maxAge"] = null;
        $app["cors.allowCredentials"] = null;
        $app["cors.exposeHeaders"] = null;

        $app["allow"] = $app->protect(new Allow());

        $app["options"] = $app->protect(function ($subject) use ($app) {
            $optionsController = function () {
                return Response::create("", 204);
            };

            if ($subject instanceof Controller) {
                $optionsRoute = $app->options($subject->getRoute()->getPath(), $optionsController)
                    ->after($app["allow"]);
            } else {
                $optionsRoute = $subject->options("{path}", $optionsController)
                    ->after($app["allow"])
                    ->assert("path", ".*");
            }

            return $optionsRoute;
        });

        $app["cors-enabled"] = $app->protect(function ($subject, $config = []) use ($app) {
            $optionsController = $app["options"]($subject);
            $cors = new Cors($config);

            if ($subject instanceof Controller) {
                $optionsController->after($cors);
            }

            $subject->after($cors);

            return $subject;
        });

        $app["cors"] = function () use ($app) {
            $app["options"]($app);
            return new Cors();
        };
    }
}
