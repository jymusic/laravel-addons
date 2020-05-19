<?php

namespace JYmusic\LaravelAddons;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Console\Application as Artisan;
use Symfony\Component\Finder\Finder;

class Registrar
{
    /**
     * register files.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param array $addons
     */
    public function register(Application $app, array $addons)
    {
        // prepare helper functions
        foreach ($addons as $addon) {
            $this->loadFiles($addon, $addon->config('addon.files', []));
        }

        foreach ($addons as $addon) {
            // load config
            $this->loadConfigurationFiles($addon);

            // regist service providers
            $providers = $addon->config('addon.providers', []);

            foreach ($providers as $provider) {
                $app->register($provider);
            }

            // register commands
            $commands = $addon->config('addon.commands', $addon->config('addon.console.commands', []));
            if (!is_array($commands)) $commands = [$commands];
            Artisan::starting(function ($artisan) use ($commands) {
                $artisan->resolveCommands($commands);
            });

            // register named middleware
            $middlewares = $addon->config('addon.middleware', $addon->config('addon.http.route_middlewares', []));
            foreach ($middlewares as $name => $middleware) {
                if (is_array($middleware)) {
                    $app['router']->middlewareGroup($name, $middleware);
                }
                else {
                    $app['router']->aliasMiddleware($name, $middleware);
                }
            }
        }
    }

    /**
     * load addon initial script files.
     *
     * @param \Jumilla\Addomnipot\Laravel\Addon $addon
     * @param array $files
     */
    protected function loadFiles(Addon $addon, array $files)
    {
        foreach ($files as $filename) {
            $path = $addon->path($filename);

            if (!file_exists($path)) {
                $message = "Warning: PHP Script '$path' is nothing.";
                info($message);
                echo $message;
                continue;
            }

            require_once $path;
        }
    }

    /**
     * Load the configuration items from all of the files.
     *
     * @param \Jumilla\Addomnipot\Laravel\Addon $addon
     */
    protected function loadConfigurationFiles(Addon $addon)
    {
        $directoryPath = $addon->path($addon->config('addon.paths.config', 'config'));

        foreach ($this->getConfigurationFiles($directoryPath) as $group => $path) {
            $addon->setConfig($group, require $path);
        }
    }

    /**
     * Get all of the configuration files for the directory.
     *
     * @param string $directoryPath
     *
     * @return array
     */
    protected function getConfigurationFiles($directoryPath)
    {
        $files = [];

        if (is_dir($directoryPath)) {
            foreach (Finder::create()->files()->in($directoryPath) as $file) {		
                $group = basename($file->getRealPath(), '.php');
                $files[$group] = $file->getRealPath();
            }
        }

        return $files;
    }

    /**
     * boot addon.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param array $addons
     */
    public function boot(Application $app, array $addons)
    {
        foreach ($addons as $addon) {
            $this->registerPackage($app, $addon);
        }
    }

    /**
     * Register the package's component namespaces.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Jumilla\Addomnipot\Laravel\Addon $addon
     */
    protected function registerPackage(Application $app, Addon $addon)
    {
        $namespace = $addon->name();

        $lang = $addon->path($addon->config('addon.paths.lang', 'lang'));
        if (is_dir($lang)) {
            $app['translator']->addNamespace($namespace, $lang);
        }

        $view = $addon->path($addon->config('addon.paths.views', 'views'));
        if (is_dir($view)) {
            $app['view']->addNamespace($namespace, $view);
        }

        /*
        $spec = $addon->path($addon->config('addon.paths.specs', 'specs'));

        if (is_dir($spec)) {
            $app['specs']->addNamespace($namespace, $spec);
        }
        */
    }
}
