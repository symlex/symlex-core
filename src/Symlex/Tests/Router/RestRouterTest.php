<?php

namespace Symlex\Tests\Router;

use TestTools\TestCase\UnitTestCase;
use Symlex\Router\RestRouter;

class RestRouterTest extends UnitTestCase
{
    /**
     * @var RestRouter
     */
    protected $router;

    public function setUp()
    {
        $this->router = $this->get('router.rest');
    }

    public function testRoute () {
        $this->router->route();
    }
}