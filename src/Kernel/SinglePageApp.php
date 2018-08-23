<?php

namespace Symlex\Kernel;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class SinglePageApp extends App
{
    protected $urlPrefix = '';

    public function __construct($appPath, $debug = false)
    {
        parent::__construct('web', $appPath, $debug);
    }

    public function init()
    {
        if ($this->debug) {
            ini_set('display_errors', 1);
        }
    }

    public function getUrlPrefix($urlPrefixPostfix = '')
    {
        return $this->urlPrefix . $urlPrefixPostfix;
    }

    public function setUrlPrefix($urlPrefix)
    {
        $this->urlPrefix = $urlPrefix;
    }

    protected function setUp()
    {
        $container = $this->getContainer();

        // The error router catches errors and displays them as error pages
        $container->get('router.error')->route();

        // Routing for REST API calls
        $container->get('router.rest')->route($this->getUrlPrefix('/api'), 'controller.rest.');

        // All other requests are routed to a default controller action (client-side routing e.g. with Vue.js)
        $container->get('router.twig_default')->route($this->getUrlPrefix(), 'controller.web.index', 'index');
    }
}