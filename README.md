Symlex Core: A micro-kernel and router components for Silex
===========================================================

[![Build Status](https://travis-ci.org/lastzero/symlex-core.png?branch=master)](https://travis-ci.org/lastzero/symlex-core)
[![Latest Stable Version](https://poser.pugx.org/lastzero/symlex-core/v/stable.svg)](https://packagist.org/packages/lastzero/symlex-core)
[![Total Downloads](https://poser.pugx.org/lastzero/symlex-core/downloads.svg)](https://packagist.org/packages/lastzero/symlex-core)
[![License](https://poser.pugx.org/lastzero/symlex-core/license.svg)](https://packagist.org/packages/lastzero/symlex-core)

*Note: This repository contains the bootstrap and routers as reusable components. For more information and a 
complete framework based on symlex-core please go to https://github.com/lastzero/symlex*

Bootstrap
---------
The light-weight Symlex kernel bootstraps **Silex** (`Symlex\Bootstrap\WebApp`) and **Symfony Console**
(`Symlex\Bootstrap\ConsoleApp`) applications. 
It's based on the [di-microkernel](https://github.com/lastzero/di-microkernel) library. The kernel itself is just 
about 400 lines of code to set a bunch of default parameters for your application and create a service container instance with that.

YAML files located in `config/` configure the application and all of it's dependencies as a service. The filename matches 
the application's environment name (e.g. `config/console.yml`). The configuration can additionally be modified 
for sub environments such as local or production by providing a matching config file like `config/console.local.yml`
(see `app.sub_environment` parameter). These files are in the same [well documented](https://symfony.com/doc/current/components/dependency_injection.html) format you might know from Symfony:

```yaml
parameters:
    app.name: 'My App'
    app.version: '1.0'

services:
    doctrine.migrations.migrate:
        class: Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand
        
    app:
        class: Symfony\Component\Console\Application
        arguments: [%app.name%, %app.version%]
        calls:
            - [ add, [ "@doctrine.migrations.migrate" ] ]
```

This provides a uniform approach for bootstrapping Web applications like `Silex\Application` or command-line 
applications like `Symfony\Component\Console\Application` using the same kernel. The result is much cleaner and 
leaner than the usual bootstrap and configuration madness you know from many frameworks.

Caching
-------

If debug mode is turned off, the service container configuration is cached by the kernel in the directory set as cache path. You have to delete all cache files after updating the configuration. To disable caching completely, add `container.cache: false` to your configuration parameters: 

```yaml
parameters:
    container.cache: false
```

Web App Container
-----------------

As an alternative to Symfony bundles, `Symlex\Bootstrap\WebApps` is capable of running multiple apps based on `Symlex\Bootstrap\App` on the same Symlex installation:

```php
$app = new WebApps('web', __DIR__ . '/../app', false);
$app->run();
```

It's bootstrapped like a regular WebApp and subsequently bootstaps other Symlex apps according to the configuration in `app/config/web.guests.yml` (path, debug, prefix and domain are optional; bootstrap and config are required):

```yaml
example:
    prefix: /example
    domain: www.example.com
    bootstrap: \Symlex\Bootstrap\WebApp
    config: web.yml
    debug: true
    path: vendors/lastzero/example/app

default:
    bootstrap: \Symlex\Bootstrap\WebApp
    config: web.default.yml
```

*Note: Assets in web/ like images, CSS or JavaScript in are not automatically shared in a way Assetic does this with Symfony bundles. If your apps not only provide Web services, you might have to create symbolic links or modify your HTML templates.*

Routers
-------
There are three router classes included in this library (they configure Silex to perform the actual routing). After routing a request to the appropriate controller action, the router subsequently renders the response to ease controller testing (actions never directly return JSON or HTML):

- `Symlex\Router\RestRouter` handles REST requests (JSON)
- `Symlex\Router\ErrorRouter` renders exceptions as error messages (HTML or JSON)
- `Symlex\Router\TwigRouter` renders regular Web pages via Twig (HTML)

It's easy to create your own custom routing/rendering based on the existing examples.

The application's HTTP kernel class initializes the routers that were configured in the service container:
```php
<?php

namespace Symlex\Bootstrap;

class WebApp extends App
{
    public function __construct($appPath, $debug = false)
    {
        if($debug) {
            ini_set('display_errors', 1);
        }

        parent::__construct('web', $appPath, $debug);
    }

    public function boot () {
        parent::boot();

        $container = $this->getContainer();

        $container->get('router.error')->route();
        $container->get('router.rest')->route('/api', 'controller.rest.');
        $container->get('router.twig')->route('', 'controller.web.');
    }
}
```

The REST and Twig routers accept optional URL (e.g. `/api`) and service name prefixes (e.g. `controller.rest.`).

Routing examples for the default HTTP kernel (`Symlex\Bootstrap\WebApp`):
- `GET /` will be routed to `controller.web.index` service's `indexAction(Request $request)`
- `POST /session/login` will be routed to `controller.web.session` service's `postLoginAction(Request $request)`
- `GET /api/users` will be routed to `controller.rest.users` service's `cgetAction(Request $request)`
- `POST /api/users` will be routed to `controller.rest.users` service's `postAction(Request $request)`
- `OPTIONS /api/users` will be routed to `controller.rest.users` service's `coptionsAction(Request $request)`
- `GET /api/users/123` will be routed to `controller.rest.users` service's `getAction($id, Request $request)`
- `OPTIONS /api/users/123` will be routed to `controller.rest.users` service's `optionsAction($id, Request $request)`
- `GET /api/users/123/comments` will be routed to `controller.rest.users` service's `cgetCommentsAction($id, Request $request)`
- `GET /api/users/123/comments/5` will be routed to `controller.rest.users` service's `getCommentsAction($id, $commendId, Request $request)`
- `PUT /api/users/123/comments/5` will be routed to `controller.rest.users` service's `putCommentsAction($id, $commendId, Request $request)`

The routers pass on the request instance to each matched controller action as last argument. It contains request parameters and headers: http://symfony.com/doc/current/book/http_fundamentals.html#requests-and-responses-in-symfony

Controller actions invoked by **TwigRouter** can either return nothing (the matching Twig template will be rendered), an array (the Twig template can access the values as variables) or a string (redirect URL). 

REST controller actions (invoked by **RestRouter**) always return arrays, which are automatically converted to valid JSON. Delete actions can return null ("204 No Content").

Interceptors
------------
HTTP interceptors can be used to perform HTTP authentication or other actions (e.g. blocking certain IP ranges) **before** routing a request:

```php
<?php

use Symlex\Bootstrap\App;

class HttpApp extends App
{
    public function __construct($appPath, $debug = false)
    {
        parent::__construct('web', $appPath, $debug);
    }

    public function boot () {
        parent::boot();

        $container = $this->getContainer();

        /*
         * In app/config/web.yml:
         *
         * services:
         *     http.interceptor:
         *         class: Symlex\Router\HttpInterceptor
         */
        $interceptor = $container->get('http.interceptor');
        $interceptor->digestAuth('Realm', array('foouser' => 'somepassword'));

        $container->get('router.error')->route();
        $container->get('router.rest')->route('/api', 'controller.rest.');
        $container->get('router.twig')->route('', 'controller.web.');
    }
}
```

