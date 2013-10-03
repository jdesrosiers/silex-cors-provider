<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsServiceProvider implements ServiceProviderInterface
{
    public function boot(Application $app)
    {
        // Add OPTIONS method support for all routes
        $app->flush();

        $allow = array();
        foreach ($app["routes"] as $route) {
            $path = $route->getPath();
            if (!array_key_exists($path, $allow)) {
                $allow[$path] = array("methods" => array(), "requirements" => array());
            }
            $allow[$path]["methods"] = array_merge($allow[$path]["methods"], $route->getMethods());
            $allow[$path]["requirements"] = array_merge($allow[$path]["requirements"], $route->getRequirements());
        }

        foreach ($allow as $path => $routeDetails) {
            $methods = $routeDetails["methods"];
            $controller = $app->match($path, function () use ($methods) {
                return new Response("", 204, array("Allow" => implode(",", $methods)));
            })->method('OPTIONS');

            unset($routeDetails["requirements"]["_method"]);
            $controller->setRequirements($routeDetails["requirements"]);
        }
    }

    public function register(Application $app)
    {
        $app["cors.allowOrigin"] = null; // Defaults to all
        $app["cors.allowMethods"] = null; // Defaults to all
//        $app["cors.allowHeaders"] = "*";
        $app["cors.maxAge"] = null;
        $app["cors.allowCredentials"] = false;
        $app["cors.exposeHeaders"] = null;

        $app["cors"] = $app->protect(function (Request $request, Response $response) use ($app) {
            if (!$request->headers->has("Origin")) {
                // Not a valid CORS request
                return;
            }

            if ($request->getMethod() === "OPTIONS" && $request->headers->has("Access-Control-Request-Method")) {
                $allowMethods = is_null($app["cors.allowMethods"]) ? $response->headers->get("Allow") : $app["cors.allowMethods"];

                if (!in_array($request->headers->get("Access-Control-Request-Method"), preg_split("/\s*,\s*/", $allowMethods))) {
                    // Not a valid prefight request
                    return;
                }

                if ($request->headers->has("Access-Control-Request-Headers")) {
                    // TODO: Allow cors.allowHeaders to be set and use it to validate the request
                    $response->headers->set("Access-Control-Allow-Headers", $request->headers->get("Access-Control-Request-Headers"));
                }

                $response->headers->set("Access-Control-Allow-Methods", $allowMethods);

                if (isset($app["cors.maxAge"])) {
                    $response->headers->set("Access-Control-Max-Age", $app["cors.maxAge"]);
                }
            } elseif (!is_null($app["cors.exposeHeaders"])) {
                $response->headers->set("Access-Control-Expose-Headers", $app["cors.exposeHeaders"]);
            }

            $origin = $request->headers->get("Origin");
            $allowedOrigins = is_null($app["cors.allowOrigin"]) ? array($origin) : preg_split('/\s+/', $app["cors.allowOrigin"]);
            $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : "null";
            $response->headers->set("Access-Control-Allow-Origin", $allowOrigin);

            if ($app["cors.allowCredentials"]) {
                $response->headers->set("Access-Control-Allow-Credentials", "true");
            }
        });
    }
}
