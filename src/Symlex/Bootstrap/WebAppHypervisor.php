<?php

namespace Symlex\Bootstrap;

use Symfony\Component\HttpFoundation\Request;
use Symlex\Bootstrap\Exception\Exception;

class WebAppHypervisor extends AppHypervisor
{
    protected $request;

    public function __construct($environment = 'web', $appPath = '', $debug = false)
    {
        if ($debug) {
            ini_set('display_errors', 1);
        }

        parent::__construct($environment, $appPath, $debug);
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        if (!$this->request) {
            $this->setRequest(Request::createFromGlobals());
        }

        return $this->request;
    }

    protected function findGuestAppConfig()
    {
        $result = false;

        $domain = $this->getRequest()->getHost();

        foreach ($this->guestsApps as $guestAppConfig) {
            $appPrefix = isset($guestAppConfig['prefix']) ? $guestAppConfig['prefix'] : '';
            $appDomain = isset($guestAppConfig['domain']) ? $guestAppConfig['domain'] : '*';

            if(strpos($this->getRequest()->getRequestUri(), $appPrefix . '/') === 0) {
                if ($appDomain === '*' || $appDomain === $domain) {
                    if(!$result || (substr_count($appPrefix, '/') > substr_count($result['prefix'], '/'))) {
                        $result = $guestAppConfig;
                    }
                }
            }
        }

        if (!$result) {
            throw new Exception('Could not find matching app and no default app configured');
        }

        return $result;
    }

    protected function configureGuestApp(App $app)
    {
        $config = $this->getGuestAppConfig();

        if (method_exists($app, 'setUrlPrefix') && isset($config['prefix'])) {
            $app->setUrlPrefix($config['prefix']);
        }
    }

    protected function getGuestAppInstance () {
        $config = $this->getGuestAppConfig();

        if(!isset($config['bootstrap'])) {
            throw new Exception('"bootstrap" parameter must be set to a valid class name');
        }

        $guestAppClass = $config['bootstrap'];
        $guestAppPath = $this->getGuestAppPath();

        $result = new $guestAppClass ($guestAppPath, $this->debug);

        return $result;
    }
}