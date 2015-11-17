<?php

namespace Symlex\Bootstrap;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symlex\Bootstrap\Exception\Exception;
use Symfony\Component\Yaml\Parser as YamlParser;

class WebAppContainer extends App
{
    protected $apps = array();
    protected $request;
    protected $yamlParser;

    public function __construct($environment = 'web', $appPath = '', $debug = false)
    {
        if ($debug) {
            ini_set('display_errors', 1);
        }

        $this->yamlParser = new YamlParser();

        parent::__construct($environment, $appPath, $debug);

        $this->loadAppsConfig();
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

    public function loadAppsConfig()
    {
        $apps = $this->yamlParser->parse(file_get_contents($this->getConfigPath() . '/' . $this->getEnvironment() . '-apps.yml'));

        foreach ($apps as $name => $app) {
            $this->addApp($name, $app);
        }
    }

    public function addApp($name, $app)
    {
        // Replace relative with absolute paths
        foreach($app as $key => $value) {
            if(substr($key, -4) === 'path' && strpos($value, '/') !== 0) {
                $app[$key] = realpath($this->getAppPath() . '/../' . $value);
            }
        }

        $app = array('label' => $name) + $app;

        $prefix = isset($app['prefix']) ? $app['prefix'] : '/';
        $domain = isset($app['domain']) ? $app['domain'] : '*';

        $this->apps[$domain][$prefix] = $app;
    }

    public function getActiveApp()
    {
        $urlParts = explode('/', $this->getRequest()->getRequestUri());
        $prefix = count($urlParts) > 1 ? '/' . $urlParts[1] : '/';
        $domain = $this->getRequest()->getHost();

        if (isset($this->apps[$domain][$prefix])) {
            $result = $this->apps[$domain][$prefix];
        } elseif (isset($this->apps[$domain]['/'])) {
            $result = $this->apps[$domain]['/'];
        } elseif (isset($this->apps['*'][$prefix])) {
            $result = $this->apps['*'][$prefix];
        } elseif (isset($this->apps['*']['/'])) {
            $result = $this->apps['*']['/'];
        } else {
            throw new Exception('Could not find matching app and no default app configured');
        }

        return $result;
    }

    public function getContainerCacheFilename()
    {
        $environment = $this->getEnvironment();
        $app = $this->getActiveApp();

        $filename = $this->getCachePath() . '/' . $environment . '_' . $app['label'] . '_container.php';

        return $filename;
    }

    protected function getActiveAppConfigPath () {
        $activeApp = $this->getActiveApp();

        if(isset($activeApp['config_path'])) {
            $result = $activeApp['config_path'];
        } else {
            $result = isset($activeApp['path']) ? $activeApp['path'] . '/config' : $this->getConfigPath();
        }

        return $result;
    }

    protected function getActiveAppPath () {
        $activeApp = $this->getActiveApp();

        $result = isset($activeApp['path']) ? $activeApp['path'] : $this->getAppPath();

        return $result;
    }

    protected function loadContainerConfiguration()
    {
        parent::loadContainerConfiguration();

        $activeApp = $this->getActiveApp();

        foreach($activeApp as $key => $value) {
            $this->getContainer()->setParameter('app.' . $key, $value);
        }

        $configPath = $this->getConfigPath();
        $environment = $this->getEnvironment();

        if(isset($activeApp['config'])) {
            $activeAppConfigPath = $this->getActiveAppConfigPath();

            $activeAppConfigLoader = new YamlFileLoader($this->getContainer(), new FileLocator($activeAppConfigPath));

            if (file_exists($activeAppConfigPath . '/' . $activeApp['config'])) {
                $activeAppConfigLoader->load($activeApp['config']);
            }
        }

        $localConfigLoader = new YamlFileLoader($this->getContainer(), new FileLocator($configPath));

        if (file_exists($configPath . '/' . $environment . '.' . $activeApp['label'] . '.yml')) {
            $localConfigLoader->load($environment . $activeApp['label'] . '.yml');
        }

        if (file_exists($configPath . '/' . $environment . '.' . $activeApp['label'] . '.local.yml')) {
            $localConfigLoader->load($environment . $activeApp['label'] . '.local.yml');
        }
    }

    protected function getApplication()
    {
        $this->boot();

        $activeApp = $this->getActiveApp();

        $appClass = $activeApp['bootstrap'];
        $appPath = $this->getActiveAppPath();

        $bootstrap = new $appClass ($appPath, $this->debug);

        if($bootstrap instanceof App) {
            $bootstrap->setContainer($this->getContainer());
        }

        if(method_exists($bootstrap, 'setUrlPrefix')) {
            $bootstrap->setUrlPrefix($activeApp['prefix']);
        }

        return $bootstrap;
    }
}