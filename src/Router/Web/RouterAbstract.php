<?php

namespace Symlex\Router\Web;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symlex\Application\Web;
use Symlex\Exception\NotFoundException;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 * @see https://github.com/symlex/symlex-core#routers
 */
abstract class RouterAbstract
{
    protected $app;
    protected $container;

    public function __construct(Web $app, ContainerInterface $container)
    {
        $this->app = $app;
        $this->container = $container;
    }

    public function getController($serviceName)
    {
        try {
            $result = $this->container->get($serviceName);
        } catch (ContainerExceptionInterface $e) {
            throw new NotFoundException ($e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new NotFoundException ($e->getMessage());
        }

        return $result;
    }

    public function hasPermission(Request $request): bool
    {
        return true;
    }
}