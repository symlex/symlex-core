<?php

namespace Symlex\Tests\Router\Web;

use Psr\Container\ContainerInterface;
use Symlex\Application\Web;
use Symfony\Component\HttpFoundation\Request;
use Symlex\Tests\Router\FakeWebController;
use TestTools\TestCase\UnitTestCase;
use Symlex\Router\Web\TwigDefaultRouter;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class TwigDefaultRouterTest extends UnitTestCase
{
    /**
     * @var TwigDefaultRouter
     */
    protected $router;

    /**
     * @var Web
     */
    protected $app;

    /**
     * @var FakeWebController
     */
    protected $controller;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setUp()
    {
        $this->container = $this->getContainer();
        $this->app = $this->container->get('app.web');
        $this->router = $this->container->get('router.web.twig_default');
        $this->controller = $this->container->get('controller.web.fake');
    }

    public function testIndexRoute()
    {
        $request = Request::create('http://localhost/fake/index', Request::METHOD_GET);
        $this->router->route('/', 'controller.web.fake');
        $response = $this->app->handle($request);
        $this->assertEquals('indexAction', $response->getContent());
        $this->assertEquals('indexAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
    }

    public function testPostIndexRoute()
    {
        $request = Request::create('http://localhost/fake/index', Request::METHOD_POST);
        $this->router->route('/', 'controller.web.fake');
        $response = $this->app->handle($request);
        $this->assertEquals('postIndexAction', $response->getContent());
        $this->assertEquals('postIndexAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
    }

    public function testGetFooRoute()
    {
        $request = Request::create('http://localhost/fake/foo/345', Request::METHOD_GET);
        $this->router->route('/', 'controller.web.fake');
        $response = $this->app->handle($request);
        $this->assertEquals('indexAction', $response->getContent());
        $this->assertEquals('indexAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
    }

    public function testHeadFooRoute()
    {
        $request = Request::create('http://localhost/fake/foo/345', Request::METHOD_HEAD);
        $this->router->route('/', 'controller.web.fake');
        $response = $this->app->handle($request);
        $this->assertEquals('indexAction', $response->getContent());
        $this->assertEquals('indexAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
    }
}