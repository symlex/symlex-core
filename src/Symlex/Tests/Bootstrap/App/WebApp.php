<?php

namespace Symlex\Tests\Bootstrap\App;

class WebApp extends \Symlex\Bootstrap\WebApp
{
    public function setUp()
    {
        $container = $this->getContainer();

        $container->get('router.error')->route();
        $container->get('router.rest')->route($this->getUrlPrefix('/api'), 'controller.rest.');
    }
}