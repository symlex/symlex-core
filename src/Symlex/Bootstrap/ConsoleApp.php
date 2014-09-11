<?php

namespace Symlex\Bootstrap;

class ConsoleApp extends App
{
    public function __construct($appPath, $debug = false)
    {
        parent::__construct('console', $appPath, $debug);
    }

    public function boot()
    {
        chdir($this->getAppPath());
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        parent::boot();
    }
}