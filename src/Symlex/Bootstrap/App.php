<?php

namespace Symlex\Bootstrap;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

class App
{
    protected $environment;
    protected $container;
    protected $appPath;
    protected $debug;
    protected $name;
    protected $version = '1.0';

    public function __construct($environment = 'dev', $appPath = '', $debug = false)
    {
        $this->environment = $environment;
        $this->debug = $debug;
        $this->appPath = $appPath;

        $this->boot();
    }

    protected function boot()
    {
        if($this->debug) {
            $this->container = new ContainerBuilder(new ParameterBag($this->getAppParameters()));
            $this->loadContainerConfiguration();
        } else {
            $environment =  $this->getEnvironment();
            $filename = $this->getCachePath() . '/' . $environment . '_container.php';

            if (file_exists($filename)) {
                require_once($filename);
                $this->container = new \ProjectServiceContainer();
            } else {
                $this->container = new ContainerBuilder(new ParameterBag($this->getAppParameters()));
                $this->loadContainerConfiguration();
                $this->container->compile();

                $dumper = new PhpDumper($this->container);
                file_put_contents($filename, $dumper->dump());
            }
        }
    }

    /**
     * @return Container
     * @throws Exception
     */
    public function getContainer () {
        if(!$this->container) {
            throw new Exception ('Container not set - maybe boot() was not executed?');
        }

        return $this->container;
    }

    public function getName()
    {
        if (null === $this->name) {
            $this->name = ucfirst(preg_replace('/[^a-zA-Z0-9_]+/', '', basename($this->getAppPath())));
        }

        return $this->name;
    }

    public function setName($appName)
    {
        $this->name = $appName;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($appVersion)
    {
        $this->version = $appVersion;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getCharset()
    {
        return 'UTF-8';
    }

    public function getLogPath()
    {
        return realpath($this->getAppPath() . '/../var/log');
    }

    public function getConfigPath()
    {
        return $this->getAppPath() . '/config';
    }

    public function getCachePath()
    {
        return realpath($this->getAppPath() . '/../var/cache');
    }

    public function getAppPath()
    {
        if ($this->appPath == '') {
            $r = new \ReflectionObject($this);
            $this->appPath = str_replace('\\', '/', dirname($r->getFileName()));
        }

        return $this->appPath;
    }

    public function getAppParameters()
    {
        return array(
            'app.name' => $this->getName(),
            'app.version' => $this->getVersion(),
            'app.environment' => $this->environment,
            'app.debug' => $this->debug,
            'app.charset' => $this->getCharset(),
            'app.path' => $this->getAppPath(),
            'app.cache_path' => $this->getCachePath(),
            'app.log_path' => $this->getLogPath(),
            'app.config_path' => $this->getConfigPath(),
        );
    }

    protected function loadContainerConfiguration()
    {
        $configPath = $this->getConfigPath();
        $environment=  $this->getEnvironment();

        $loader = new YamlFileLoader($this->container, new FileLocator($configPath));

        if (file_exists($configPath . '/' . $environment . '.yml')) {
            $loader->load($environment . '.yml');
        }

        if (file_exists($configPath . '/' . $environment . '.local.yml')) {
            $loader->load($environment . '.local.yml');
        }
    }

    public function getApplication()
    {
        return $this->getContainer()->get('app');
    }

    public function run()
    {
        return $this->getApplication()->run();
    }
}