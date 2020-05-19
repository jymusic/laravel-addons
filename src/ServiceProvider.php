<?php

namespace JYmusic\LaravelAddons;

use Illuminate\Support\Facades\Blade;
use JYmusic\LaravelAddons\Addon\Generator as AddonGenerator;
use JYmusic\LaravelAddons\Addon\Repository;
use JYmusic\LaravelAddons\Addon\Events\AddonWorldCreated;
use JYmusic\LaravelAddons\Addon\Events\AddonRegistered;
use JYmusic\LaravelAddons\Addon\Events\AddonBooted;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Addon environment.
     *
     * @var \Jumilla\Addomnipot\Laravel\Environment
     */
    protected $addonEnvironment;

    /**
     * @var array
     */
    protected $addons;

    /**
     * @var array
     */
    protected $commands = [
        Console\AddonMakeCommand::class,
        Console\AddonListCommand::class,
        Console\AddonNameCommand::class,
        Console\AddonRemoveCommand::class,
        Console\AddonStatusCommand::class,
    ];

    /**
     * Register the service provider.
     */
    public function register()
    {
        $app = $this->app;

        // register addon environment
        $app->instance('addon', $this->addonEnvironment = new Environment($app));
        $app->alias('addon', AddonEnvironment::class);

        // 注册命令行
        $this->commands($this->commands);

        $this->registerClassResolvers();

        (new Registrar)->register($app, $this->addonEnvironment->addons());
    }

    /**
     */
    protected function registerClassResolvers()
    {
        $addons = $this->addonEnvironment->addons();

        ClassLoader::register($this->addonEnvironment, $addons);

        AliasResolver::register($this->app['path'], $addons, $this->app['config']->get('app.aliases', []));
    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $app = $this->app;

        $this->publishes([
            __DIR__ . '/../config/addon.php' => config_path('addon.php')
        ]);

        // boot all addons
        (new Registrar)->boot($app, $this->addonEnvironment->addons());
    }
}

