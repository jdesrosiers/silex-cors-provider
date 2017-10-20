<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    private $options;

    public function __construct($options = [])
    {
        $this->options = $options;
    }

    public function __invoke(Request $request, Response $response, Application $app)
    {
        $defaults = [
            "allowOrigin" => $app["cors.allowOrigin"],
            "allowMethods" => $app["cors.allowMethods"],
            "allowHeaders" => $app["cors.allowHeaders"],
            "maxAge" => $app["cors.maxAge"],
            "allowCredentials" => $app["cors.allowCredentials"],
            "exposeHeaders" => $app["cors.exposeHeaders"]
        ];
        $this->cors($request, $response, $this->options + $defaults);
    }

    private function cors(Request $request, Response $response, $options)
    {
        $headers = [];

        if (!$this->isCorsRequest($request)) {
            return [];
        }

        if ($this->isPreflightRequest($request)) {
            $requestMethod = $request->headers->get("Access-Control-Request-Method");
            $allow = $response->headers->get("Allow");
            if (!$this->isMethodAllowed($requestMethod, $allow, $options["allowMethods"])) {
                return [];
            }

            $requestHeaders = $request->headers->get("Access-Control-Request-Headers");
            if (!$this->areHeadersAllowed($requestHeaders, $options["allowHeaders"])) {
                return [];
            }

            $headers["Access-Control-Allow-Headers"] = $requestHeaders;
            $headers["Access-Control-Allow-Methods"] = $requestMethod;
            $headers["Access-Control-Max-Age"] = $options["maxAge"];
        } else {
            $headers["Access-Control-Expose-Headers"] = $options["exposeHeaders"];
        }

        $allowOrigin = $this->allowOrigin($request, $options["allowOrigin"]);

        if (!$allowOrigin) {
            return [];
        }

        $headers["Access-Control-Allow-Origin"] = $allowOrigin;
        $headers["Access-Control-Allow-Credentials"] = $this->allowCredentials($options["allowCredentials"]);

        $response->headers->add(array_filter($headers));
    }

    private function isCorsRequest(Request $request)
    {
        return $request->headers->has("Origin");
    }

    private function isPreflightRequest(Request $request)
    {
        return $request->getMethod() === "OPTIONS" && $request->headers->has("Access-Control-Request-Method");
    }

    private function isMethodAllowed($requestMethod, $allow, $allowMethods)
    {
        $commaSeparatedMethods = !is_null($allowMethods) ? $allowMethods : $allow;
        $allowedMethods = array_filter(preg_split("/\s*,\s*/", $commaSeparatedMethods));
        return in_array($requestMethod, $allowedMethods);
    }

    private function areHeadersAllowed($commaSeparatedRequestHeaders, $allowHeaders)
    {
        if ($allowHeaders === null) {
            return true;
        }
        $requestHeaders = array_filter(preg_split("/\s*,\s*/", $commaSeparatedRequestHeaders));
        $allowedHeaders = array_filter(preg_split("/\s*,\s*/", $allowHeaders));
        return array_diff($requestHeaders, $allowedHeaders) === [];
    }

    private function allowOrigin(Request $request, $allowOrigin)
    {
        $origin = $request->headers->get("Origin");
        if ($allowOrigin === "*") {
            $allowOrigin = $origin;
        }

        $origins = array_filter(preg_split('/\s+/', $allowOrigin));
        foreach ($origins as $domain) {
            if (preg_match($this->domainToRegex($domain), $origin)) {
                return $origin;
            }
        }

        return false;
    }

    private function domainToRegex($domain)
    {
        $star = $this->doubleQuote("*");
        $slash = $this->doubleQuote("/");
        $dot = $this->doubleQuote(".");
        $wildcard = "/(?<=^|$slash)$star(?=$dot)/";
        $subdomain = "[^.]+";
        $quotedDomain = preg_quote($domain, "/");
        return "/^" . preg_replace($wildcard, $subdomain, $quotedDomain) . "$/";
    }

    private function doubleQuote($subject)
    {
        return preg_quote(preg_quote($subject, "/"), "/");
    }

    private function allowCredentials($allowCredentials)
    {
        return $allowCredentials === true ? "true" : null;
    }
}
