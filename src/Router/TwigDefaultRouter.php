<?php

namespace Symlex\Router;

use Symfony\Component\HttpFoundation\Request;
use Symlex\Router\Exception\NotFoundException;
use Symlex\Router\Exception\AccessDeniedException;
use Symlex\Router\Exception\MethodNotAllowedException;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 * @see https://github.com/symlex/symlex-core#routers
 */
class TwigDefaultRouter extends TwigRouter
{
    public function route(string $routePrefix = '', string $serviceName = 'controller.web.index', string $action = 'index')
    {
        $app = $this->app;
        $container = $this->container;

        $handler = function (string $path, Request $request) use ($container, $serviceName, $action) {
            $prefix = strtolower($request->getMethod());

            $actionName = $prefix . $action . 'Action';

            $controllerName = 'default';

            $controllerInstance = $this->getController($serviceName);

            if ($prefix == 'get' && !method_exists($controllerInstance, $actionName)) {
                $actionName = $action . 'Action';
            }

            if (!method_exists($controllerInstance, $actionName)) {
                if (method_exists($controllerInstance, $action . 'Action')) {
                    throw new MethodNotAllowedException ($request->getMethod() . ' not supported');
                } else {
                    throw new NotFoundException ($actionName . ' not found');
                }
            }

            if (!$this->hasPermission($request)) {
                throw new AccessDeniedException ('Access denied');
            }

            $result = call_user_func_array(array($controllerInstance, $actionName),  array($request));

            $this->setTwigVariables('default', $action, $request->isXmlHttpRequest());

            $template = $this->getTemplateFilename($controllerName, $action);

            $response = $this->getResponse($result, $template);

            return $response;
        };

        $app->match($routePrefix . '/{path}', $handler)->assert('path', '.*');
    }
}