<?php

namespace Symlex\Tests\Kernel;

use PHPUnit\Framework\TestCase;
use Symlex\Tests\Kernel\App\App;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class AppCacheTest extends TestCase
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
        $this->assertFileDoesNotExist($app->getContainerCacheFilename());
    }
}