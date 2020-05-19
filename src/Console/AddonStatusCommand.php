<?php

namespace JYmusic\LaravelAddons\Console;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use JYmusic\LaravelAddons\Environment as AddonEnvironment;
use UnexpectedValueException;

class AddonStatusCommand extends Command
{
    use Functions;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'addon:status
        {addon? : Name of addon.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Addons status';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Filesystem $filesystem, AddonEnvironment $env)
    {
        // make addons/
        $addons_directory = $env->path();
        if (!$filesystem->exists($addons_directory)) {
            $filesystem->makeDirectory($addons_directory);
        }

        $addon_name = $this->argument('addon');
        if (! $addon_name) {
            $addons = $env->addons();

            $this->line('--------');
            foreach ($addons as $addon) {
                $this->dump($addon);
                $this->line('--------');
            }
        }
        else {
            $addon = $env->addon($addon_name);

            // check addon
            if ($addon === null) {
                throw new UnexpectedValueException(sprintf('Addon "%s" is not found.', $addon_name));
            }

            $this->dump($addon);
        }
    }

    protected function dump($addon)
    {
        $this->dumpProperties($addon);
        $this->dumpClasses($addon);
        $this->dumpServiceProviders($addon);
    }

    protected function dumpProperties($addon)
    {
        $this->info(sprintf('Addon "%s"', $addon->name()));
        $this->info(sprintf('Path: %s', $addon->relativePath($this->laravel)));
        $this->info(sprintf('PHP namespace: %s', $addon->phpNamespace()));
    }

    protected function dumpClasses($addon)
    {
        // load laravel services
        $files = $this->laravel['files'];
        $env = $this->laravel[AddonEnvironment::class];

        // 全ディレクトリ下を探索する (PSR-4)
        foreach ($addon->config('addon.directories', []) as $directory) {
            $this->info(sprintf('PHP classes on "%s"', $directory));

            $classDirectoryPath = $addon->path($directory);

            if (!file_exists($classDirectoryPath)) {
                $this->line(sprintf('Warning: Class directory "%s" not found', $directory));
                continue;
            }

            // recursive find files
            $phpFilePaths = iterator_to_array((new Finder())->in($classDirectoryPath)->name('*.php')->files(), false);

            foreach ($phpFilePaths as $phpFilePath) {
                $relativePath = substr($phpFilePath, strlen($classDirectoryPath) + 1);

                $classFullName = $addon->phpNamespace().'\\'.$env->pathToClass($relativePath);

                $this->line(sprintf('  "%s" => %s', $relativePath, $classFullName));
            }
        }
    }

    protected function dumpServiceProviders($addon)
    {
    }
}
