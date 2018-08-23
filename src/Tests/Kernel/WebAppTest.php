<?php

namespace Symlex\Tests\Kernel;

use TestTools\TestCase\UnitTestCase;
use Symlex\Kernel\WebApp;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class WebAppTest extends UnitTestCase
{
    /**
     * @var WebApp
     */
    protected $app;

    public function setUp()
    {
        $this->app = new WebApp(__DIR__ . '/App', true);
    }

    public function testGetName()
    {
        $result = $this->app->getName();
        $this->assertEquals('App', $result);
    }

    public function testGetVersion()
    {
        $result = $this->app->getVersion();
        $this->assertEquals('1.0', $result);
    }

    public function testGetEnvironment()
    {
        $result = $this->app->getEnvironment();
        $this->assertEquals('web', $result);
    }

    public function testGetCharset()
    {
        $result = $this->app->getCharset();
        $this->assertEquals('UTF-8', $result);
    }

    public function testGetAppParameters()
    {
        $result = $this->app->getContainerParameters();
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('app.name', $result);
        $this->assertArrayHasKey('app.version', $result);
        $this->assertArrayHasKey('app.environment', $result);
        $this->assertArrayHasKey('app.debug', $result);
        $this->assertArrayHasKey('app.charset', $result);
        $this->assertArrayHasKey('app.path', $result);
        $this->assertArrayHasKey('app.cache_path', $result);
        $this->assertArrayHasKey('app.log_path', $result);
        $this->assertArrayHasKey('app.config_path', $result);
    }

    public function testGetContainer()
    {
        $result = $this->app->getContainer();

        $this->assertInstanceOf('\Symfony\Component\DependencyInjection\Container', $result);
    }

    public function testRun()
    {
        ob_start();
        $this->app->run();
        $result = ob_get_clean();

        $this->assertEquals('Hello World!', $result);
    }
}