<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Application $app, Request $request, Response $response)
    {
        if (!$this->isCorsRequest($request)) {
            return array();
        }

        $headers = array();
        if ($this->isPreflightRequest($request)) {
            $requestMethod = $request->headers->get("Access-Control-Request-Method");
            $allowedMethods = $app["cors.allowMethods"] ?: $response->headers->get("Allow");

            if (!$this->isRequestMethodAllowed($requestMethod, $allowedMethods)) {
                return array();
            }

            // TODO: Allow cors.allowHeaders to be set and use it to validate the request
            $headers["Access-Control-Allow-Headers"] = $request->headers->get("Access-Control-Request-Headers");
            $headers["Access-Control-Allow-Methods"] = $allowedMethods;
            $headers["Access-Control-Max-Age"] = $app["cors.maxAge"];
        } else {
            $headers["Access-Control-Expose-Headers"] = $app["cors.exposeHeaders"];
        }

        $headers["Access-Control-Allow-Origin"] = $this->accessControlAllowOrigin(
            $request->headers->get("Origin"),
            $app["cors.allowOrigin"]
        );
        $headers["Access-Control-Allow-Credentials"] = $app["cors.allowCredentials"] === true ? "true" : null;

        return array_filter($headers);
    }

    protected function isCorsRequest(Request $request)
    {
        return $request->headers->has("Origin");
    }

    protected function isPreflightRequest(Request $request)
    {
        return $request->getMethod() === "OPTIONS" && $request->headers->has("Access-Control-Request-Method");
    }

    protected function accessControlAllowOrigin($origin, $allowedOrigins)
    {
        return $allowedOrigins === "*" || in_array($origin, preg_split("/\s+/", $allowedOrigins)) ? $origin : "null";
    }

    protected function isRequestMethodAllowed($requestMethod, $allowMethods)
    {
        return in_array($requestMethod, preg_split("/\s*,\s*/", $allowMethods));
    }
}
