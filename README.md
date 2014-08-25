Symlex core components
======================

[![Build Status](https://travis-ci.org/lastzero/symlex-core.png?branch=master)](https://travis-ci.org/lastzero/symlex-core)

https://github.com/lastzero/symlex contains more documentation and a complete application based on symlex-core.

**The goal of this project is to simplify Silex development by providing a working system that favors convention over configuration.**

Bootstrap
---------
YAML files located in `$appPath/config/` configure the entire system via dependecy injection. The filename matches the application's environment name (e.g. `app.yml`):

```
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

        $this->boot();
    }
    
    ...
}
```

These files are in the same format you know from Symfony 2. In addition to the regular services, they also contain the actual application as a service ("app"):

    services:
        app:
            class: Silex\Application

This provides a uniform approach for bootstrapping Web and command-line applications with the same kernel.

If debug mode is turned off, the dependency injection container configuration is cached in `var/cache/`. You have to delete all cache files after updating the configuration. To disable caching completely, add `container.cache: false` to your configuration parameters (usually in `app/config/parameters.yml`): 

    parameters:
        container.cache: false

A light-weight kernel bootstraps the application. It's just about 150 lines of code, initializes the Symfony dependency injection container and then starts the app by calling `run()`:

```
<?php
namespace Symlex\Bootstrap;

class App
{
    ...
    
    public function getApplication()
    {
        return $this->getContainer()->get('app');
    }
    
    public function run()
    {
        return $this->getApplication()->run();
    }
    
    ...
}
```

The kernel base class can be extended to customize it for a specific purpose:

```
<?php
namespace Symlex\Bootstrap;

class ConsoleApp extends App
{
    public function __construct($appPath, $debug = false)
    {
        parent::__construct('console', $appPath, $debug);
    }

    public function boot()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        parent::boot();
    }
}
```

Creating a kernel instance and calling run() is enough to start an application:

```
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symlex\Bootstrap\ConsoleApp;

$app = new ConsoleApp (__DIR__);

$app->run();
```

Router
------
There are three router classes included in this library (they configure Silex to perform the actual routing). After routing a request to the appropriate controller action, the router subsequently renders the response to ease controller testing (actions never directly return JSON or HTML):

- `Symlex\Router\RestRouter` handles REST requests (JSON)
- `Symlex\Router\ErrorRouter` renders exceptions as error messages (HTML or JSON)
- `Symlex\Router\TwigRouter` renders regular Web pages via Twig (HTML)

It's easy to create your own custom routing/rendering based on the existing examples.

The application's HTTP (WebApp) kernel class initializes routing and sets optional URL/service name prefixes:
```
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

Examples (based on this routing configuration):
- `GET /` will be routed to `controller.web.index` service's `indexAction(Request $request)`
- `POST /session/login` will be routed to `controller.web.session` service's `postLoginAction(Request $request)`
- `GET /api/user` will be routed to `controller.rest.user` service's `cgetAction(Request $request)`
- `GET /api/user/123` will be routed to `controller.rest.user` service's `getAction($id, Request $request)`
- `POST /api/user` will be routed to `controller.rest.user` service's `postAction(Request $request)`
- `PUT /api/user/123/item/5` will be routed to `controller.rest.user` service's `putItemAction($id, $itemId, Request $request)`
