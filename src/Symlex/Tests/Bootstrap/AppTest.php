<?php

namespace Symlex\Tests\Bootstrap;

use TestTools\TestCase\UnitTestCase;
use Symlex\Tests\Bootstrap\App\App;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class AppTest extends UnitTestCase
{
    /**
     * @var App
     */
    protected $app;

    public function setUp()
    {
        $this->app = new App('symlex_test', __DIR__ . '/App', true);
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
        $this->assertEquals('symlex_test', $result);
    }

    public function testGetCharset()
    {
        $result = $this->app->getCharset();
        $this->assertEquals('UTF-8', $result);
    }

    public function testGetAppParameters()
    {
        $result = $this->app->getAppParameters();
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
}