<?php

namespace Symlex\Tests\Router\Web;

use TestTools\TestCase\UnitTestCase;
use Symlex\Router\Web\ErrorRouter;

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

    public function setUp(): void
    {
        $this->router = $this->get('router.web.error');
    }

    public function testRoute()
    {
        $this->router->route();
        $this->assertTrue(true);
    }
}