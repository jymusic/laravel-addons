<?php

namespace {$namespace}\Providers;

use JYmusic\LaravelAddons\Support\DatabaseServiceProvider as ServiceProvider;
use {$namespace}\Migrations;
use {$namespace}\Seeds;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap database services.
     *
     * @return void
     */
    public function boot()
    {
        $this->migrations('{$addon_name}', [
            '1.0' => Migrations\{$migration_class_name}::class,
        ]);

        $this->seeds([
//            '{$addon_name}-test' => Seeds\Test::class,
        ]);
    }
}
