<?php

namespace Symlex\Tests\Bootstrap;

use PHPUnit_Framework_TestCase as UnitTestCase;
use Symlex\Tests\Bootstrap\App\App;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class AppCacheTest extends UnitTestCase
{
    public function testCaching()
    {
        $app = new App('symlex_test', __DIR__ . '/App', false);
        $result = $app->getContainer();
        $this->assertInstanceOf('\Symfony\Component\DependencyInjection\Container', $result);

        $filename = $app->getContainerCacheFilename();
        $this->assertFileExists($filename);

        $appCached = new App('symlex_test', __DIR__ . '/App', false);
        $this->assertInstanceOf('\Symfony\Component\DependencyInjection\Container', $appCached->getContainer());

        unlink($filename);
    }

    public function testCachingDisabled()
    {
        $app = new App('symlex_test_nocache', __DIR__ . '/App', false);
        $result = $app->getContainer();
        $this->assertInstanceOf('\Symfony\Component\DependencyInjection\Container', $result);
        $this->assertFileNotExists($app->getContainerCacheFilename());
    }
}