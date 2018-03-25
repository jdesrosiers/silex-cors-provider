<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Allow
{
    public function __invoke(Request $request, Response $response, Application $app)
    {
        $requestMethod = $app["request_context"]->getMethod();
        $app["request_context"]->setMethod("NOTAMETHOD");

        try {
            $app["request_matcher"]->match($request->getPathInfo());
            $allow = []; // Should never get here
        } catch (MethodNotAllowedException $e) {
            $allow = array_filter($e->getAllowedMethods(), function ($method) {
                return $method != "OPTIONS";
            });
        } catch (ResourceNotFoundException $e) {
            $allow = []; // Should never get here
        }

        $app["request_context"]->setMethod($requestMethod);

        if (count($allow) === 0) {
            throw new NotFoundHttpException();
        }

        $response->headers->set("Allow", implode(",", $allow));

        return $response;
    }
}
