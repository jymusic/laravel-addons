<?php

namespace Jumilla\Addomnipot\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Config\Repository;
use RuntimeException;

class Addon
{
    /**
     * @param \Illuminate\Contracts\Foundation\Application  $app
     * @param string $name
     * @param string $path
     *
     * @return static
     */
    public static function create($app, $name, $path)
    {
        $pathComponents = explode('/', $path);

        $config = static::loadAddonConfig($path, $name);

        return new static($app, $name, $path, $config);
    }

    /**
     * @param string $path
     * @param string $name
     *
     * @return array
     */
    protected static function loadAddonConfig($path, $name)
    {
        if (file_exists($path.'/addon.php')) {
            $config = require $path.'/addon.php';
        } else {
            throw new RuntimeException("No such config file for addon '$name', need 'addon.php'.");
        }

        $version = array_get($config, 'version', 5);
        if ($version != 5) {
            throw new RuntimeException($version.': Illigal addon version.');
        }

        return $config;
    }

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @param \Illuminate\Contracts\Foundation\Application  $app
     * @param string  $name
     * @param string  $path
     * @param array   $config
     */
    public function __construct($app, $name, $path, array $config)
    {
        $this->app = $app;
        $this->name = $name;
        $this->path = $path;
        $this->config = new Repository();
        $this->config->set('addon', $config);
    }

    /**
     * get name.
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * get fullpath.
     *
     * @param string $path
     *
     * @return string
     */
    public function path($path = null)
    {
        if (func_num_args() == 0) {
            return $this->path;
        } else {
            return $this->path.'/'.$path;
        }
    }

    /**
     * get relative path.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return string
     */
    public function relativePath(Application $app)
    {
        return substr($this->path, strlen($app->basePath()) + 1);
    }

    /**
     * get version.
     *
     * @return int
     */
    public function version()
    {
        return $this->config('addon.version', 5);
    }

    /**
     * get PHP namespace.
     *
     * @return string
     */
    public function phpNamespace()
    {
        return trim($this->config('addon.namespace', ''), '\\');
    }

    /**
     * get config value.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return $this->config->get($key, $default);
    }

    /**
     * set config value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setConfig($key, $value)
    {
        $this->config->set($key, $value);
    }

    /**
     * Get a lang resource name
     *
     * @param string $resource
     *
     * @return string
     */
    public function transName($resource)
    {
        return $this->name.'::'.$resource;
    }

    /**
     * Translate the given message.
     *
     * @param string $id
     * @param array $parameters
     * @param string $domain
     * @param string $locale
     * @return string
     */
    public function trans()
    {
        $args = func_get_args();
        $args[0] = $this->transName($args[0]);

        return call_user_func_array([$this->app['translator'], 'trans'], $args);
    }

    /**
     * Translates the given message based on a count.
     *
     * @param string $id
     * @param int $number
     * @param array $parameters
     * @param string $domain
     * @param string $locale
     * @return string
     */
    public function transChoice()
    {
         $args = func_get_args();
         $args[0] = $this->transName($args[0]);

         return call_user_func_array([$this->app['translator'], 'transChoice'], $args);
    }

    /**
     * Get a view resource name
     *
     * @param string $resource
     *
     * @return string
     */
    public function viewName($resource)
    {
        return $this->name.'::'.$resource;
    }

    /**
     * @param string $view
     * @param array $data
     * @param array $mergeData
     *
     * @return \Illuminate\View\View
     */
    public function view($view, $data = [], $mergeData = [])
    {
        return $this->app['view']->make($this->viewname($view), $data, $mergeData);
    }

    /**
     * Get a spec resource name
     *
     * @param string $resource
     *
     * @return string
     */
    public function specName($resource)
    {
        return $this->name.'::'.$resource;
    }

    /**
     * Get spec.
     *
     * @param string $path
     *
     * @return \Jumilla\Addomnipot\Laravel\Specs\InputSpec
     */
    public function spec($path)
    {
        return $this->app[SpecFactory::class]->make($this->specName($path));
    }
}
