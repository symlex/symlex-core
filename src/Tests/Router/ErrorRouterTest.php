<?php

namespace Symlex\Tests\Router;

use TestTools\TestCase\UnitTestCase;
use Symlex\Router\ErrorRouter;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class ErrorRouterTest extends UnitTestCase
{
    /**
     * @var ErrorRouter
     */
    protected $router;

    public function setUp()
    {
        $this->router = $this->get('router.error');
    }

    public function testRoute()
    {
        $this->router->route();
        $this->assertTrue(true);
    }
}