<?php

namespace Symlex\Router;

use Silex\Application;
use Twig_Environment;
use Twig_Error_Loader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class ErrorRouter
{
    protected $app;
    protected $twig;
    protected $exceptionCodes = array();
    protected $exceptionMessages = array();
    protected $debug = false;

    public function __construct(Application $app, Twig_Environment $twig, array $exceptionCodes, array $exceptionMessages, $debug = false)
    {
        $this->app = $app;
        $this->twig = $twig;

        $this->exceptionCodes = $exceptionCodes;
        $this->exceptionMessages = $exceptionMessages;

        $this->debug = $debug;
    }

    protected function isJsonRequest (Request $request) {
        $result = false;

        $headers = $request->headers;

        if(strpos($headers->get('Accept'), 'application/json') !== false) {
            $result = true;
        }

        if(strpos($headers->get('Content-Type'), 'application/json') !== false) {
            $result = true;
        }

        return $result;
    }

    public function route()
    {
        $app = $this->app;
        $exceptionCodes = $this->exceptionCodes;

        $app->error(function (\Exception $e, $code) use ($app, $exceptionCodes) {
            $request = $app['request'];
            $exceptionClass = get_class($e);

            if (isset($exceptionCodes[$exceptionClass])) {
                $code = (int)$exceptionCodes[$exceptionClass];
            } else {
                $code = 500;
            }

            if ($this->isJsonRequest($request)) {
                return $this->jsonError($e, $code);
            } else {
                return $this->htmlError($e, $code);
            }
        });
    }

    protected function getErrorDetails (\Exception $exception, $code) {
        if (isset($this->exceptionMessages[$code])) {
            $error = $this->exceptionMessages[$code];
        } else {
            $error = $exception->getMessage();
        }

        if ($this->debug) {
            $message = $exception->getMessage();

            if (empty($message)) {
                $message = $error;
            }

            $class = get_class($exception);
            $file = $exception->getFile();
            $line = $exception->getLine();
            $trace = $exception->getTrace();
        } else {
            $message = $error;
            $class = 'Exception';
            $file = '';
            $line = '';
            $trace = array();
        }

        $result = array(
            'error' => $error,
            'message' => $message,
            'code' => $code,
            'class' => $class,
            'file' => $file,
            'line' => $line,
            'trace' => $trace
        );

        return $result;
    }

    protected function jsonError(\Exception $exception, $code)
    {
        $values = $this->getErrorDetails($exception, $code);

        return $this->app->json($values, $code);
    }

    protected function htmlError(\Exception $exception, $code)
    {
        $values = $this->getErrorDetails($exception, $code);

        try {
            $result = $this->twig->render('error/' . $code . '.twig', $values);
        } catch (Twig_Error_Loader $e) {
            $result = $this->twig->render('error/default.twig', $values);
        }

        return new Response($result, $code);
    }
}