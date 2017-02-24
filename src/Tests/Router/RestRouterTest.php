<?php

namespace Symlex\Tests\Router;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TestTools\TestCase\UnitTestCase;
use Symlex\Router\RestRouter;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class RestRouterTest extends UnitTestCase
{
    /**
     * @var RestRouter
     */
    protected $router;

    /**
     * @var Application
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
        $this->app = $this->container->get('app');
        $this->router = $this->container->get('router.rest');
        $this->controller = $this->container->get('controller.rest.fake');
    }

    public function testCgetRoute()
    {
        $request = Request::create('http://localhost/api/fake');
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
        $request = Request::create('http://localhost/api/fake/345');
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
        $request = Request::create('http://localhost/api/fake/345/comment/1', 'OPTIONS');
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