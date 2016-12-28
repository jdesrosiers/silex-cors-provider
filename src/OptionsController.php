<?php

namespace JDesrosiers\Silex\Provider;

use Symfony\Component\HttpFoundation\Response;

class OptionsController
{
    private $methods;

    public function __construct($methods)
    {
        $this->methods = $methods;
    }

    public function __invoke()
    {
        return Response::create("", 204, array("Access-Control-Allow-Methods" => implode(",", $this->methods), "Allow" => implode(",", $this->methods)));
    }
}
