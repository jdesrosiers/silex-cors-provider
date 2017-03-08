<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class OptionsController
{
    public function __invoke(Application $app, Request $request)
    {
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

        $app["request_context"]->setMethod("OPTIONS");

        if (count($allow) === 0) {
            throw new NotFoundHttpException();
        }

        return Response::create("", 204, ["Allow" => implode(",", $allow)]);
    }
}
