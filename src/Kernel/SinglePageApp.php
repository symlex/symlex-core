<?php

namespace Symlex\Kernel;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class SinglePageApp extends WebApp
{
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