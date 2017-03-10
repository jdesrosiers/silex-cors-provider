<?php

namespace JDesrosiers\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Silex\Controller;
use Silex\ControllerCollection;
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
        $app->options("{route}", new OptionsController())
            ->assert("route", ".+");

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
    public function register(Container $app)
    {
        $app["cors.allowOrigin"] = "*"; // Defaults to all
        $app["cors.allowMethods"] = null; // Defaults to all
        $app["cors.allowHeaders"] = null; // Defaults to all
        $app["cors.maxAge"] = null;
        $app["cors.allowCredentials"] = null;
        $app["cors.exposeHeaders"] = null;

        $app["cors"] = $app->protect(new Cors());

        $app["cors-enabled"] = $app->protect(function ($subject, $options = []) use ($app) {
            if ($subject instanceof Controller) {
                $app->options($subject->getRoute()->getPath(), new OptionsController())
                    ->after(new Cors($options));
            } else if ($subject instanceof ControllerCollection) {
                $subject->options("{path}", new OptionsController())
                    ->assert("path", ".*");
            }
            $subject->after(new Cors($options));

            return $subject;
        });
    }
}
