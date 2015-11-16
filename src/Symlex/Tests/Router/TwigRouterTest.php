<?php

namespace Symlex\Tests\Router;

use TestTools\TestCase\UnitTestCase;
use Symlex\Router\TwigRouter;

class TwigRouterTest extends UnitTestCase
{
    /**
     * @var TwigRouter
     */
    protected $router;

    public function setUp()
    {
        $this->router = $this->get('router.twig');
    }

    public function testRoute () {
        $this->router->route();
    }
}