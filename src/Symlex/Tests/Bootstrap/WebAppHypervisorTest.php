<?php

namespace Symlex\Tests\Bootstrap;

use Symfony\Component\HttpFoundation\Request;
use TestTools\TestCase\UnitTestCase;
use Symlex\Bootstrap\WebAppHypervisor;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class WebAppContainerTest extends UnitTestCase
{
    /**
     * @var WebAppHypervisor
     */
    protected $app;

    public function testRunWeb () {
        $request = Request::create('http://www.bar.com/web/api/example/99');

        $this->app = new WebAppHypervisor('web', __DIR__ . '/ContainerApp', true);

        $this->app->setRequest($request);

        ob_start();
        $this->app->run($request);
        $result = ob_get_clean();

        $this->assertContains('{"id":"99","foo":"baz"}', $result);
    }

    public function testRunExampleCom () {
        $request = Request::create('http://www.example.com/foo/api/example/88');

        $this->app = new WebAppHypervisor('web', __DIR__ . '/ContainerApp', true);

        $this->app->setRequest($request);

        ob_start();
        $this->app->run($request);
        $result = ob_get_clean();

        $this->assertContains('{"id":"88","foo":"baz"}', $result);
    }

    public function testRunNotFound () {
        $request = Request::create('http://www.example2.com/foo/api/example/88');

        $this->app = new WebAppHypervisor('web', __DIR__ . '/ContainerApp', true);

        $this->app->setRequest($request);

        ob_start();
        $this->app->run($request);
        $result = ob_get_clean();

        $this->assertContains('Sorry, the page you are looking for could not be found', $result);
    }
}