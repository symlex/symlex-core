<?php

namespace Symlex\Router;

use Silex\Application;
use Twig_Environment;
use Twig_Error_Loader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
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

    protected function getRequest(): Request
    {
        $result = $this->app['request_stack']->getCurrentRequest();

        return $result;
    }

    protected function isJsonRequest(): bool
    {
        $headers = $this->getRequest()->headers;

        $result = false;

        if (strpos($headers->get('Accept'), 'application/json') !== false) {
            $result = true;
        }

        if (strpos($headers->get('Content-Type'), 'application/json') !== false) {
            $result = true;
        }

        return $result;
    }

    public function route()
    {
        $exceptionCodes = $this->exceptionCodes;

        $this->app->error(function (\Exception $e) use ($exceptionCodes) {
            $exceptionClass = get_class($e);

            if (isset($exceptionCodes[$exceptionClass])) {
                $httpCode = (int)$exceptionCodes[$exceptionClass];
            } else {
                $httpCode = 500;
            }

            if ($this->isJsonRequest()) {
                return $this->jsonError($e, $httpCode);
            } else {
                return $this->htmlError($e, $httpCode);
            }
        });
    }

    protected function getErrorDetails(\Exception $exception, int $httpCode): array
    {
        if (isset($this->exceptionMessages[$httpCode])) {
            $error = $this->exceptionMessages[$httpCode];
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
            $message = '';
            $class = 'Exception';
            $file = '';
            $line = '';
            $trace = array();
        }

        $result = array(
            'error' => $error,
            'message' => $message,
            'code' => $httpCode,
            'class' => $class,
            'file' => $file,
            'line' => $line,
            'trace' => $trace
        );

        return $result;
    }

    protected function jsonError(\Exception $exception, int $httpCode): Response
    {
        $values = $this->getErrorDetails($exception, $httpCode);

        return $this->app->json($values, $httpCode);
    }

    protected function htmlError(\Exception $exception, int $httpCode): Response
    {
        $values = $this->getErrorDetails($exception, $httpCode);

        try {
            $result = $this->twig->render('error/' . $httpCode . '.twig', $values);
        } catch (Twig_Error_Loader $e) {
            $result = $this->twig->render('error/default.twig', $values);
        }

        return new Response($result, $httpCode);
    }
}