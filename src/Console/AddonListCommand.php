<?php

namespace JYmusic\LaravelAddons\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use JYmusic\LaravelAddons\Environment as AddonEnvironment;

class AddonListCommand extends Command
{
    use Functions;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'addon:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List up addon information';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Filesystem $filesystem, AddonEnvironment $env)
    {
        // make addons/
        $addonsDirectory = $env->path();
        if (!$filesystem->exists($addonsDirectory)) {
            $filesystem->makeDirectory($addonsDirectory);
        }

        // copy app/config/addon.php
        $addonConfigSourceFile = __DIR__.'/../../config/addon.php';
        $addonConfigFile = $this->laravel['path.config'].'/addon.php';

        if (!$filesystem->exists($addonConfigFile)) {
            $filesystem->copy($addonConfigSourceFile, $addonConfigFile);

            $this->info('make config: '.$addonConfigFile);
        }

        // show lists
        $addons = $env->addons();
        foreach ($addons as $addon) {
            $this->dump($addon);
        }
    }

    protected function dump($addon)
    {
        $path = $addon->relativePath($this->laravel);

        $this->line("<info>{$addon->name()}</info> {$path}");
    }
}
