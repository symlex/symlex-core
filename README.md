Symlex Core: A micro-kernel based on the Symfony service container plus router components for Silex 2
=====================================================================================================

[![Build Status](https://travis-ci.org/lastzero/symlex-core.png?branch=master)](https://travis-ci.org/lastzero/symlex-core)
[![Latest Stable Version](https://poser.pugx.org/lastzero/symlex-core/v/stable.svg)](https://packagist.org/packages/lastzero/symlex-core)
[![Total Downloads](https://poser.pugx.org/lastzero/symlex-core/downloads.svg)](https://packagist.org/packages/lastzero/symlex-core)
[![License](https://poser.pugx.org/lastzero/symlex-core/license.svg)](https://packagist.org/packages/lastzero/symlex-core)

*Note: This repository contains the bootstrap and routers as reusable components. For more information and a 
complete framework based on symlex-core please go to https://github.com/lastzero/symlex*

Bootstrap
---------
The light-weight Symlex kernel (`Symlex\Bootstrap\App`) bootstraps **Silex** and **Symfony Console** applications. It's just about 300 lines of code, initializes the Symfony service container and then starts the app by calling `run()`:

```php
<?php
namespace Symlex\Bootstrap;

class App
{
    protected $environment;
    protected $debug;
    protected $appPath;

    public function __construct($environment = 'app', $appPath = '', $debug = false)
    {
        $this->environment = $environment;
        $this->debug = $debug;
        $this->appPath = $appPath;

        $this->init();
    }
    
    ...
    
    public function getApplication()
    {
        if($this->appIsUninitialized()) {
            $this->setUp();
        }

        $result = $this->getContainer()->get('app');

        $this->appInitialized = true;

        return $result;
    }
    
    public function run()
    {
        $arguments = func_get_args();
        $application = $this->getApplication();

        return call_user_func_array(array($application, 'run'), $arguments);
    }
}
```

YAML files located in `$appPath/config/` configure the entire system via dependecy injection. The filename matches the application's environment name (e.g. `console.yml`). These files are in the same format you know from Symfony 2. In addition to the regular services, they also contain the actual application as a service ("app"):

    services:
        app:
            class: Silex\Application

This provides a uniform approach for bootstrapping Web (`Silex\Application`) and command-line (`Symfony\Component\Console\Application`) applications with the same kernel.

The kernel base class can be extended to customize it for a specific purpose:

```php
<?php
namespace Symlex\Bootstrap;

class ConsoleApp extends App
{
    public function __construct($appPath, $debug = false)
    {
        parent::__construct('console', $appPath, $debug);
    }

    public function setUp()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
    }
}
```

Creating a kernel instance and calling run() is enough to start an application:

```php
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symlex\Bootstrap\ConsoleApp;
$app = new ConsoleApp (__DIR__);
$app->run();
```

**Caching**

If debug mode is turned off, the service container configuration is cached in `var/cache/` by the kernel. You have to delete all cache files after updating the configuration. To disable caching completely, add `container.cache: false` to your configuration parameters (usually in `app/config/parameters.yml`): 

    parameters:
        container.cache: false

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
- `GET /api/user` will be routed to `controller.rest.user` service's `cgetAction(Request $request)`
- `GET /api/user/123` will be routed to `controller.rest.user` service's `getAction($id, Request $request)`
- `POST /api/user` will be routed to `controller.rest.user` service's `postAction(Request $request)`
- `PUT /api/user/123/item/5` will be routed to `controller.rest.user` service's `putItemAction($id, $itemId, Request $request)`

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

