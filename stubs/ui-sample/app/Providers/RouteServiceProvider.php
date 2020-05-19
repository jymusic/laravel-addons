<?php

namespace {$namespace}\Providers;

use Illuminate\Routing\Router;
use JYmusic\LaravelAddons\Support\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define the routes for the addon.
     *
     * @param  \Illuminate\Routing\Router  $router  (injection)
     * @return void
     */
    public function map(Router $router)
    {
        parent::map($router);
    }

    /**
     * Get addon.
     *
     * @return \JYmusic\LaravelAddons\Addon
     */
    protected function addon()
    {
        return addon();
    }
}
