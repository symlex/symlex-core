<?php

namespace Symlex\Tests\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symlex\Kernel\SinglePageApp;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class SinglePageAppTest extends WebAppTest
{
    /**
     * @var SinglePageApp
     */
    protected $app;

    public function setUp(): void
    {
        $this->app = new SinglePageApp(__DIR__ . '/App', true);
    }

    public function testRun()
    {
        $request = Request::create('https://localhost/foo/bar');

        ob_start();
        $this->app->run($request);
        $result = ob_get_clean();

        $this->assertEquals('Hello Single Page App!', $result);
    }
}