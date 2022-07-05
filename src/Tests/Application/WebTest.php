<?php

namespace Symlex\Tests\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symlex\Router\Web\ErrorRouter;
use TestTools\TestCase\UnitTestCase;
use Symlex\Application\Web;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class WebTest extends UnitTestCase
{
    public function testHandle()
    {
        $container = $this->getContainer();
        /** @var Web $app */
        $app = $container->get('app.web');

        $app->get('/foo/bar', function () {
            return new Response('fail');
        });

        $app->post('/foo/{id}', function () {
            return new Response('ok');
        });

        $request = Request::create('/foo/123', 'POST');

        $result = $app->handle($request);

        $this->assertSame('ok', $result->getContent());
    }

    public function testHandleGet()
    {
        $container = $this->getContainer();
        /** @var Web $app */
        $app = $container->get('app.web');

        $app->get('/foo/bar', function () {
            return new Response('ok');
        });

        $app->post('/foo/{id}', function () {
            return new Response('fail');
        });

        $request = Request::create('/foo/bar', 'GET');

        $result = $app->handle($request);

        $this->assertSame('ok', $result->getContent());
    }

    static public function callableMethod(Request $e, $id)
    {
        return new Response($id);
    }

    public function testHandleStaticController()
    {
        $container = $this->getContainer();
        /** @var Web $app */
        $app = $container->get('app.web');

        $app->get('/foo/{id}', 'Symlex\Tests\Application\WebTest::callableMethod');

        $request = Request::create('/foo/999', 'GET');

        $result = $app->handle($request);

        $this->assertEquals(999, $result->getContent());
    }

    public function testHeadRequest()
    {
        $container = $this->getContainer();
        /** @var Web $app */
        $app = $container->get('app.web');

        $app->get('/foo/{id}', 'Symlex\Tests\Application\WebTest::callableMethod');

        $request = Request::create('/foo/999', 'HEAD');

        ob_start();
        $app->run($request);
        $result = ob_get_clean();

        $this->assertEquals(999, $result);
    }

    public function testHandleError()
    {
        $container = $this->getContainer();

        /** @var Web $app */
        $app = $container->get('app.web');

        /** @var ErrorRouter $errorRouter */
        $errorRouter = $container->get('router.web.error');

        $errorRouter->route();

        $app->get('/foo/bar', function () {
            return new Response(print_r(func_get_args(), true));
        });

        $app->post('/foo/{id}', function () {
            return new Response(print_r(func_get_args(), true));
        });

        $request = Request::create('/not/exists', 'GET');

        $result = $app->handle($request);

        $this->assertStringContainsString('<h1>500 Looks like something went wrong!</h1>', $result->getContent());
    }
}