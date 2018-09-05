<?php

namespace Symlex\Tests\Router\Web;

use Symlex\Application\Web;
use Symfony\Component\HttpFoundation\Request;
use TestTools\TestCase\UnitTestCase;
use Symlex\Router\Web\RestRouter;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class RestRouterTest extends UnitTestCase
{
    /**
     * @var RestRouter
     */
    protected $router;

    /**
     * @var Web
     */
    protected $app;

    /**
     * @var FakeRestController
     */
    protected $controller;

    /**
     * @var Container
     */
    protected $container;

    public function setUp()
    {
        $this->container = $this->getContainer();
        $this->app = $this->container->get('app.web');
        $this->router = $this->container->get('router.web.rest');
        $this->controller = $this->container->get('controller.rest.fake');
    }

    public function testCgetRoute()
    {
        $request = Request::create('http://localhost/api/fake', Request::METHOD_GET);
        $this->router->route('/api', 'controller.rest.');
        $response = $this->app->handle($request);
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('cgetAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('request', $result);
        $this->assertArrayHasKey('actionName', $result);
        $this->assertEquals('cgetAction', $result['actionName']);
        $this->assertInternalType('array', $result['request']);
    }

    public function testGetRoute()
    {
        $request = Request::create('http://localhost/api/fake/345', Request::METHOD_GET);
        $this->router->route('/api', 'controller.rest.');
        $response = $this->app->handle($request);
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('getAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('request', $result);
        $this->assertArrayHasKey('actionName', $result);
        $this->assertEquals('getAction', $result['actionName']);
        $this->assertEquals(345, $result['id']);
        $this->assertInternalType('array', $result['request']);
    }

    public function testHeadRoute()
    {
        $request = Request::create('http://localhost/api/fake/345', Request::METHOD_HEAD);
        $this->router->route('/api', 'controller.rest.');
        $response = $this->app->handle($request);
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('getAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('request', $result);
        $this->assertArrayHasKey('actionName', $result);
        $this->assertEquals('getAction', $result['actionName']);
        $this->assertEquals(345, $result['id']);
        $this->assertInternalType('array', $result['request']);
    }

    public function testOptionsCommentRoute()
    {
        $request = Request::create('http://localhost/api/fake/345/comment/1', Request::METHOD_OPTIONS);
        $this->router->route('/api', 'controller.rest.');
        $response = $this->app->handle($request);
        $result = json_decode($response->getContent(), true);
        $this->assertEquals('optionsCommentAction', $this->controller->actionName);
        $this->assertInstanceOf(Request::class, $this->controller->request);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('request', $result);
        $this->assertArrayHasKey('actionName', $result);
        $this->assertEquals('optionsCommentAction', $result['actionName']);
        $this->assertEquals(345, $result['id']);
        $this->assertEquals(1, $result['commentId']);
        $this->assertInternalType('array', $result['request']);
    }
}