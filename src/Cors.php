<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function __invoke(Request $request, Response $response)
    {
        if (!$request->headers->has("Origin")) {
            // Not a CORS request
            return;
        }

        if ($request->getMethod() === "OPTIONS" && $request->headers->has("Access-Control-Request-Method")) {
            // Preflight Request
            $allow = $response->headers->get("Allow");
            $allowMethods = !is_null($this->app["cors.allowMethods"]) ? $this->app["cors.allowMethods"] : $allow;

            $requestMethod = $request->headers->get("Access-Control-Request-Method");
            if (!in_array($requestMethod, preg_split("/\s*,\s*/", $allowMethods))) {
                // Not a valid prefight request
                return;
            }

            if ($request->headers->has("Access-Control-Request-Headers")) {
                // TODO: Allow cors.allowHeaders to be set and use it to validate the request
                $requestHeaders = $request->headers->get("Access-Control-Request-Headers");
                $response->headers->set("Access-Control-Allow-Headers", $requestHeaders);
            }

            $response->headers->set("Access-Control-Allow-Methods", $allowMethods);

            if (!is_null($this->app["cors.maxAge"])) {
                $response->headers->set("Access-Control-Max-Age", $this->app["cors.maxAge"]);
            }
        } elseif (!is_null($this->app["cors.exposeHeaders"])) {
            // Actual Request
            $response->headers->set("Access-Control-Expose-Headers", $this->app["cors.exposeHeaders"]);
        }

        if ($this->app["cors.allowOrigin"] === '*') {
            $this->app["cors.allowOrigin"] = null;
        }

        $origin = $request->headers->get("Origin");
        if (is_null($this->app["cors.allowOrigin"])) {
            $this->app["cors.allowOrigin"] = $origin;
        }

        $allowOrigin = in_array($origin, preg_split('/\s+/', $this->app["cors.allowOrigin"])) ? $origin : "null";
        $response->headers->set("Access-Control-Allow-Origin", $allowOrigin);

        if ($this->app["cors.allowCredentials"] === true) {
            $response->headers->set("Access-Control-Allow-Credentials", "true");
        }
    }
}
