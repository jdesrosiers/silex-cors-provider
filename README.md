silex-cors-provider
===================

[![Build Status](https://travis-ci.org/jdesrosiers/silex-cors-provider.png?branch=master)](https://travis-ci.org/jdesrosiers/silex-cors-provider)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/jdesrosiers/silex-cors-provider/badges/quality-score.png?s=d593c5178dadb082de1ff21a806906867e46888c)](https://scrutinizer-ci.com/g/jdesrosiers/silex-cors-provider/)
[![Code Coverage](https://scrutinizer-ci.com/g/jdesrosiers/silex-cors-provider/badges/coverage.png?s=b4b2acc88ac7db8452058ba2b66ef2b3bbb42deb)](https://scrutinizer-ci.com/g/jdesrosiers/silex-cors-provider/)

The CorsServiceProvider provides CORS middleware for your silex application.  It also goes through all routes and generates
all necessary OPTIONS methods.

Installation
------------
Install the silex-cors-provider using [composer](http://getcomposer.org/).  This project uses [sematic versioning](http://semver.org/).

```json
{
    "require": {
        "jdesrosiers/silex-cors-provider": "~0.1"
    }
}
```

Parameters
----------
* **cors.allowOrigin**: (string) Space separated set of allowed access.  Defaults to all.
* **cors.allowMethods**: (string) Comma separated set of allowed HTTP methods.  Defaults to all.
* **cors.maxAge**: (int) The number of seconds a CORS pre-flight response can be cached.
* **cors.allowCredentials**: (boolean) Are cookies allowed?
* **cors.exposeHeaders**: (boolean)

Services
--------
* **cors**: A function that can be added as after middleware to the Application, a ControllerCollection, or a Route.

Registering
-----------
```php
$app->register(new CorsServiceProvider(), array(
    "cors.allowOrigin" => "http://petstore.swagger.wordnik.com",
));
```

Usage
-----
```php
$app->after($app["cors"]);
```
