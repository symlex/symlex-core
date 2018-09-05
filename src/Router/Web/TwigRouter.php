<?php

namespace Symlex\Router\Web;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symlex\Application\Web;
use Symfony\Component\DependencyInjection\Container;
use Twig_Environment;
use Symlex\Exception\NotFoundException;
use Symlex\Exception\AccessDeniedException;
use Symlex\Exception\MethodNotAllowedException;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 * @see https://github.com/symlex/symlex-core#routers
 */
class TwigRouter extends RouterAbstract
{
    protected $twig;

    public function __construct(Web $app, Container $container, Twig_Environment $twig)
    {
        parent::__construct($app, $container);

        $this->twig = $twig;
    }

    public function route(string $routePrefix = '', string $servicePrefix = 'controller.web.', string $servicePostfix = '')
    {
        $app = $this->app;
        $container = $this->container;

        $webRequestHandler = function (Request $request, string $controller, string $action = '') use ($container, $servicePrefix, $servicePostfix) {
            // indexAction is default
            if (!$action) {
                $action = 'index';
            }

            // Remove trailing .html
            if (stripos($action, '.html') === (strlen($action) - 5)) {
                $action = substr($action, 0, -5);
            }

            $method = $request->getMethod();
            $prefix = strtolower($method);
            $parts = explode('/', $action);

            $subResources = '';
            $params = array();

            $count = count($parts);

            for ($i = 0; $i < $count; $i++) {
                $subResources .= ucfirst($parts[$i]);

                if (isset($parts[$i + 1])) {
                    $i++;
                    $params[] = $parts[$i];
                }
            }

            $params[] = $request;
            $actionName = $prefix . $subResources . 'Action';

            $controllerService = $servicePrefix . strtolower($controller) . $servicePostfix;

            $controllerInstance = $this->getController($controllerService);

            if (($method === Request::METHOD_GET || $method === Request::METHOD_HEAD) && !method_exists($controllerInstance, $actionName)) {
                $actionName = $subResources . 'Action';
            }

            if (!method_exists($controllerInstance, $actionName)) {
                if (method_exists($controllerInstance, $subResources . 'Action')) {
                    throw new MethodNotAllowedException ($request->getMethod() . ' not supported');
                } else {
                    throw new NotFoundException ($actionName . ' not found');
                }
            }

            if (!$this->hasPermission($request)) {
                throw new AccessDeniedException ('Access denied');
            }

            $result = call_user_func_array(array($controllerInstance, $actionName), $params);

            $this->setTwigVariables($controller, $subResources, $request->isXmlHttpRequest());

            $template = $this->getTemplateFilename($controller, $subResources);

            $response = $this->getResponse($result, $template);

            return $response;
        };

        $indexRequestHandler = function (Request $request) use ($container, $servicePrefix, $servicePostfix, $webRequestHandler) {
            return $webRequestHandler($request, 'index', 'index');
        };

        $app->get($routePrefix . '/', $indexRequestHandler);
        $app->match($routePrefix . '/{controller}', $webRequestHandler);
        $app->match($routePrefix . '/{controller}/{action}', $webRequestHandler, ['action' => '.+']);
    }

    protected function render(string $template, array $values, int $httpCode = 200): Response
    {
        $result = $this->twig->render(strtolower($template), $values);

        return new Response($result, $httpCode);
    }

    protected function redirect(string $url, int $httpCode = 302): Response
    {
        $result = new RedirectResponse($url, $httpCode);

        return $result;
    }

    protected function setTwigVariables(string $controller, string $action, bool $isXmlHttpRequest)
    {
        $this->twig->addGlobal('controller', strtolower($controller));
        $this->twig->addGlobal('action', strtolower($action));
        $this->twig->addGlobal('ajax_request', $isXmlHttpRequest);
    }

    protected function getTemplateFilename(string $controller, string $subResources): string
    {
        $result = $controller . '/' . $subResources . '.twig';

        return $result;
    }

    protected function getResponse($result, string $template): Response
    {
        if (is_object($result) && $result instanceof Response) {
            $response = $result;
        } elseif (is_string($result) && $result != '') {
            $response = $this->redirect($result);
        } else {
            $response = $this->render($template, (array)$result);
        }

        return $response;
    }
}