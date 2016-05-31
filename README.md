silex-cors-provider
===================

[![Build Status](https://travis-ci.org/jdesrosiers/silex-cors-provider.png?branch=master)](https://travis-ci.org/jdesrosiers/silex-cors-provider)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jdesrosiers/silex-cors-provider/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jdesrosiers/silex-cors-provider/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jdesrosiers/silex-cors-provider/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jdesrosiers/silex-cors-provider/?branch=master)

The CorsServiceProvider provides [CORS](http://enable-cors.org/) support as middleware for your silex application.  CORS
allows you to make AJAX requests across domains.  CORS uses OPTIONS requests to make preflight requests.  Because silex
doesn't have functionality for serving OPTIONS request by default, this service goes through all of your routes and
generates the necessary OPTIONS routes.

Installation
------------
Install the silex-cors-provider using [composer](http://getcomposer.org/).  This project uses [sematic versioning](http://semver.org/).

```bash
composer require jdesrosiers/silex-cors-provider "~1.0"
```

Parameters
----------
* **cors.allowOrigin**: (string) Space separated set of allowed domains.  Defaults to all.
* **cors.allowMethods**: (string) Comma separated set of allowed HTTP methods.  Defaults to all.
* **cors.maxAge**: (int) The number of seconds a CORS pre-flight response can be cached.  Defaults to 0.
* **cors.allowCredentials**: (boolean) Are cookies allowed?  Defaults to false.
* **cors.exposeHeaders**: (string) Space separated set of headers that are safe to expose.  Defaults to all.

Services
--------
* **cors**: A function that can be added as after middleware to the Application, a ControllerCollection, or a Route.

Registering
-----------
```php
$app->register(new JDesrosiers\Silex\Provider\CorsServiceProvider(), array(
    "cors.allowOrigin" => "http://petstore.swagger.wordnik.com",
));
```

Usage
-----
The following shows how to add CORS functionality to the entire application.  It can also be applied to a
ControllerCollection a Route to limit its scope.
```php
$app->after($app["cors"]);
```
