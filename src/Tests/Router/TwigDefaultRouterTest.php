<?php

namespace Symlex\Tests\Router;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TestTools\TestCase\UnitTestCase;
use Symlex\Router\TwigDefaultRouter;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class TwigDefaultRouterTest extends UnitTestCase
{
    /**
     * @var TwigDefaultRouter
     */
    protected $router;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var FakeWebController
     */
    protected $controller;

    /**
     * @var Container
     */
    protected $container;

    public function setUp()
    {
        $this->container = $this->getContainer();
        $this->app = $this->container->get('app');
        $this->router = $this->container->get('router.twig_default');
        $this->controller = $this->container->get('controller.web.fake');
    }

    public function testIndexRoute()
    {
        $request = Request::create('http://localhost/fake/index');
        $this->router->route('/', 'controller.web.fake');
        $response = $this->app->handle($request);
        $this->assertEquals('indexAction', $response->getContent());
        $this->assertEquals('indexAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
    }

    public function testPostIndexRoute()
    {
        $request = Request::create('http://localhost/fake/index', 'POST');
        $this->router->route('/', 'controller.web.fake');
        $response = $this->app->handle($request);
        $this->assertEquals('postIndexAction', $response->getContent());
        $this->assertEquals('postIndexAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
    }

    public function testFooRoute()
    {
        $request = Request::create('http://localhost/fake/foo/345', 'GET');
        $this->router->route('/', 'controller.web.fake');
        $response = $this->app->handle($request);
        $this->assertEquals('indexAction', $response->getContent());
        $this->assertEquals('indexAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
    }
}