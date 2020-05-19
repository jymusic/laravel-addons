<?php

namespace JYmusic\LaravelAddons;

use Illuminate\Contracts\Foundation\Application;
use UnexpectedValueException;

class Environment
{
    /**
     * @return array
     */
    protected $addons = null;

    /**
     * @return array
     */
    protected $spacePaths = [];

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->makeAddonsPaths();
    }

    /**
     * @return void
     */
    private function makeAddonsPaths()
    {
        $addonsDirectoryPath = $this->path();

        $this->spacePaths[''] = $addonsDirectoryPath;

        foreach ($this->app['config']->get('addon.additional_paths', []) as $name => $path) {
            $this->addSpace($name, $path);
        }
    }

    /**
     * @return void
     */
    public function addSpace($name, $path)
    {
        $this->spacePaths[$name] = $this->app->basePath().'/'.$path;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function path($name = null)
    {
        if ($name !== null) {
            return $this->path().'/'.$name;
        } else {
            return $this->app->basePath().'/'.$this->app['config']->get('addon.path', 'addons');
        }
    }

    /**
     * @param string $space
     * @param string $name
     *
     * @return string
     */
    public function spacePath($space, $name = null)
    {
        $path = $space ? array_get($this->spacePaths, $space) : $this->path();

        if ($path === null) {
            throw new UnexpectedValueException("addon space '{$space}' is not found.");
        }

        if ($name !== null) {
            return $path.'/'.$name;
        } else {
            return $path;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function exists($name)
    {
        if ($this->existsOnSpace(null, $name)) {
            return true;
        }

        foreach ($this->spacePaths as $space => $path) {
            if ($this->existsOnSpace($space, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function existsOnSpace($space, $name)
    {
        return is_dir($this->spacePath($space, $name));
    }

    /**
     * @param string $relativeClassName
     *
     * @return string
     */
    public function classToPath($relativeClassName)
    {
        return str_replace('\\', '/', $relativeClassName).'.php';
    }

    /**
     * @param string $relativePath
     *
     * @return mixed
     */
    public function pathToClass($relativePath)
    {
        if (strpos($relativePath, '/') !== false) {
            $relativePath = dirname($relativePath).'/'.basename($relativePath, '.php');
        } else {
            $relativePath = basename($relativePath, '.php');
        }

        return str_replace('/', '\\', $relativePath);
    }

    /**
     * @return array
     */
    public function loadAddons()
    {
        $files = $this->app['files'];
        $ignore_pattern = $this->app['config']->get('addon.ignore_pattern', '/^@/');
        $name_pattern = $this->app['config']->get('addon.name_pattern', '/^(.+)$/');

        $addons = [];

        foreach ($this->spacePaths as $path) {
            // make directory
            if (!$files->exists($path)) {
                $files->makeDirectory($path);
            }

            // load addons
            foreach ($files->directories($path) as $dir) {
                // test ignore pattern
                if (preg_match($ignore_pattern, basename($dir))) {
                    continue;
                }

                // test name pattern
                if (! preg_match($name_pattern, basename($dir), $matches)) {
                    continue;
                }

                $name = count($matches) >= 2 ? $matches[1] : $matches[0];

                $addon = Addon::create($this->app, $name, $dir);

                $addons[$addon->name()] = $addon;
            }
        }

        return $addons;
    }

    /**
     * @return array
     */
    public function addons()
    {
        if ($this->addons === null) {
            $this->addons = $this->loadAddons();
        }

        return $this->addons;
    }

    /**
     * @return \Jumilla\Addomnipot\Laravel\Addons\Addon
     */
    public function addon($name)
    {
        return array_get($this->addons(), $name ?: '', null);
    }
}
