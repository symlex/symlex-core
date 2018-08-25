<?php

namespace Symlex\Application;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symlex\Exception\InvalidResponseException;
use Symlex\Exception\InvalidRouteException;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class Web
{
    protected $errorCallback;
    protected $routeCallback;
    protected $routeCollectionBuilder;

    /**
     * Handles HTTP request and sends the response.
     *
     * @param Request|null $request Request to process
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $response = $this->handle($request);
        $response->send();
    }

    /**
     * Returns routes for request handling.
     *
     * @return RouteCollection
     */
    protected function loadRoutes(): RouteCollection
    {
        $builder = $this->getRouteCollectionBuilder();

        if (\is_callable($this->routeCallback)) {
            call_user_func($this->routeCallback, $builder);
        }

        return $builder->build();
    }

    /**
     * @param Request $request
     * @return UrlMatcher
     */
    protected function getUrlMatcher(Request $request)
    {
        $routes = $this->loadRoutes();

        $context = new RequestContext();
        $context->fromRequest($request);

        return new UrlMatcher($routes, $context);
    }

    /**
     * Handles HTTP request and returns response.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        try {
            $matcher = $this->getUrlMatcher($request);

            $parameters = $matcher->match($request->getPathInfo());

            if (empty($parameters['_controller'])) {
                throw new InvalidRouteException('Route requires a _controller callable');
            }

            if (!\is_callable($parameters['_controller'])) {
                throw new InvalidRouteException('_controller must be a callable');
            }

            $callback = $parameters['_controller'];

            $result = call_user_func_array($callback, $this->getControllerParameters($request, $parameters));

            if (!$result instanceof Response) {
                throw new InvalidResponseException('Response must be an instance of \Symfony\Component\HttpFoundation\Response');
            }
        } catch (\Exception $e) {
            $result = $this->handleException($request, $e);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param \Exception $e
     * @return Response
     */
    protected function handleException(Request $request, \Exception $e): Response
    {
        $result = null;

        if (\is_callable($this->errorCallback)) {
            try {
                $result = call_user_func($this->errorCallback, $request, $e);
            } catch (\Exception $callbackException) {
                $e = $callbackException;
            }
        }

        if (!$result instanceof Response) {
            $message = $e->getMessage();
            $class = get_class($e);
            $file = $e->getFile();
            $line = $e->getLine();
            $trace = $e->getTraceAsString();

            $error = "\nError: $message\n\n$class in $file line $line\n\n$trace\n\n";
            $error .= "Note: You can set a custom error handler using setErrorCallback()\n";
            $result = new Response($error, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $result;
    }

    /**
     * Returns arguments for controller callback.
     *
     * @param Request $request
     * @param array $parameters
     * @return array
     */
    protected function getControllerParameters(Request $request, array $parameters): array
    {
        $result = [$request];

        foreach ($parameters as $key => $value) {
            if (strpos($key, '_') !== 0) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Registers an error handler.
     *
     * Error handlers are simple callables which take the Request and a single Exception
     * as an argument. If a controller throws an exception, an error handler
     * can return a specific response.
     *
     * @param callable $callback Error handler callback, takes a Request and an Exception argument
     */
    public function setErrorCallback($callback)
    {
        $this->errorCallback = $callback;
    }

    /**
     * @param callable $callback
     */
    public function setRouteCallback($callback)
    {
        $this->routeCallback = $callback;
    }

    /**
     * @return RouteCollectionBuilder
     */
    public function getRouteCollectionBuilder(): RouteCollectionBuilder
    {
        if (!$this->routeCollectionBuilder) {
            $this->routeCollectionBuilder = new RouteCollectionBuilder();
        }

        return $this->routeCollectionBuilder;
    }

    /**
     * @param RouteCollectionBuilder $builder
     * @return $this
     */
    public function setRouteCollectionBuilder(RouteCollectionBuilder $builder)
    {
        $this->routeCollectionBuilder = $builder;

        return $this;
    }

    /**
     * @param Route $route
     * @return $this
     */
    public function addRoute(Route $route)
    {
        $this->getRouteCollectionBuilder()->addRoute($route);

        return $this;
    }

    /**
     * Maps a pattern to a callable.
     *
     * You can optionally specify HTTP methods that should be matched.
     *
     * @param string $pattern Matched route pattern
     * @param callable $callback Callback that returns the response when matched
     * @param array $requirements
     * @param array $methods
     * @return $this
     */
    public function match(string $pattern, $callback, array $requirements = [], array $methods = [])
    {
        $route = new Route(
            $pattern, // path
            array('_controller' => $callback), // default values
            $requirements, // requirements
            array(), // options
            '', // host
            array(), // schemes
            $methods // methods
        );

        $this->addRoute($route);

        return $this;
    }

    /**
     * Redirects the user to another URL.
     *
     * @param string $url The URL to redirect to
     * @param int $status The status code (302 by default)
     *
     * @return RedirectResponse
     */
    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Creates a streaming response.
     *
     * @param mixed $callback A valid PHP callback
     * @param int $status The response status code
     * @param array $headers An array of response headers
     *
     * @return StreamedResponse
     */
    public function stream($callback = null, $status = 200, array $headers = [])
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Escapes a text for HTML.
     *
     * @param string $text The input text to be escaped
     * @param int $flags The flags (@see htmlspecialchars)
     * @param string $charset The charset
     * @param bool $doubleEncode Whether to try to avoid double escaping or not
     *
     * @return string Escaped text
     */
    public function escape($text, $flags = ENT_COMPAT, $charset = null, $doubleEncode = true)
    {
        return htmlspecialchars($text, $flags, $charset ?: $this['charset'], $doubleEncode);
    }

    /**
     * Convert some data into a JSON response.
     *
     * @param mixed $data The response data
     * @param int $status The response status code
     * @param array $headers An array of response headers
     *
     * @return JsonResponse
     */
    public function json($data = [], $status = 200, array $headers = [])
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Sends a file.
     *
     * @param \SplFileInfo|string $file The file to stream
     * @param int $status The response status code
     * @param array $headers An array of response headers
     * @param null|string $contentDisposition The type of Content-Disposition to set automatically with the filename
     *
     * @return BinaryFileResponse
     */
    public function sendFile($file, $status = 200, array $headers = [], $contentDisposition = null)
    {
        return new BinaryFileResponse($file, $status, $headers, true, $contentDisposition);
    }

    /**
     * Aborts the current request by sending a proper HTTP error.
     *
     * @param int $statusCode The HTTP status code
     * @param string $message The status message
     * @param array $headers An array of HTTP headers
     */
    public function abort($statusCode, $message = '', array $headers = [])
    {
        throw new HttpException($statusCode, $message, null, $headers);
    }

    /**
     * Maps a GET request to a callable.
     *
     * @param string $pattern
     * @param callable $callback
     * @param array $requirements
     * @return $this
     */
    public function get(string $pattern, $callback, array $requirements = [])
    {
        return $this->match($pattern, $callback, $requirements, [Request::METHOD_GET]);
    }

    /**
     * Maps a POST request to a callable.
     *
     * @param string $pattern
     * @param callable $callback
     * @param array $requirements
     * @return $this
     */
    public function post(string $pattern, $callback, array $requirements = [])
    {
        return $this->match($pattern, $callback, $requirements, [Request::METHOD_POST]);
    }

    /**
     * Maps a PUT request to a callable.
     *
     * @param string $pattern
     * @param callable $callback
     * @param array $requirements
     * @return $this
     */
    public function put(string $pattern, $callback, array $requirements = [])
    {
        return $this->match($pattern, $callback, $requirements, [Request::METHOD_PUT]);
    }

    /**
     * Maps a DELETE request to a callable.
     *
     * @param string $pattern
     * @param $callback
     * @param array $requirements
     * @return $this
     */
    public function delete(string $pattern, $callback, array $requirements = [])
    {
        return $this->match($pattern, $callback, $requirements, [Request::METHOD_DELETE]);
    }

    /**
     * Maps an OPTIONS request to a callable.
     *
     * @param string $pattern
     * @param $callback
     * @param array $requirements
     * @return $this
     */
    public function options(string $pattern, $callback, array $requirements = [])
    {
        return $this->match($pattern, $callback, $requirements, [Request::METHOD_OPTIONS]);
    }

    /**
     * Maps a PATCH request to a callable.
     *
     * @param string $pattern
     * @param $callback
     * @param array $requirements
     * @return $this
     */
    public function patch(string $pattern, $callback, array $requirements = [])
    {
        return $this->match($pattern, $callback, $requirements, [Request::METHOD_PATCH]);
    }
}