<?php

namespace Symlex\Router\Web;

use Symfony\Component\HttpFoundation\Request;
use Symlex\Exception\NotFoundException;
use Symlex\Exception\AccessDeniedException;
use Symlex\Exception\MethodNotAllowedException;

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

        $handler = function (Request $request) use ($container, $serviceName, $action) {
            $method = $request->getMethod();
            $prefix = strtolower($method);

            $actionName = $prefix . $action . 'Action';

            $controllerName = 'default';

            $controllerInstance = $this->getController($serviceName);

            if (($method === Request::METHOD_GET || $method === Request::METHOD_HEAD) && !method_exists($controllerInstance, $actionName)) {
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

        $app->match($routePrefix . '/{path}', $handler, ['path' => '.*']);
    }
}