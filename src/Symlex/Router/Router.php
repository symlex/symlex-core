<?php

namespace Symlex\Router;

use Silex\Application;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symlex\Router\Exception\NotFoundException;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
abstract class Router {
    protected $app;
    protected $container;

    public function __construct(Application $app, Container $container) {
        $this->app = $app;
        $this->container = $container;
    }

    public function getController($serviceName)
    {
        try {
            $result = $this->container->get($serviceName);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundException ($e->getMessage());
        }

        return $result;
    }

    public function hasPermission(Request $request)
    {
        return true;
    }
}