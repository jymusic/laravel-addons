<?php

namespace JYmusic\LaravelAddons\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use JYmusic\LaravelAddons\Environment as AddonEnvironment;
use UnexpectedValueException;

class AddonRemoveCommand extends Command
{
    use Functions;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'addon:remove
        {addon : Name of addon.}
        {--force : Force remove.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove addon.';

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Filesystem $filesystem, AddonEnvironment $env)
    {
        $addon_name = ucfirst($this->argument('addon'));

        $addon = $env->addon($addon_name);

        // check addon
        if ($addon === null) {
            throw new UnexpectedValueException(sprintf('Addon "%s" is not found.', $addon_name));
        }

        // confirm
        $this->line('Addon name: '.$addon_name);
        $this->line('Addon path: '.$addon->relativePath($this->laravel));

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure? [y/N]', false)) {
                $this->comment('canceled');

                return;
            }
        }

        // process
        $filesystem->deleteDirectory($addon->path());

        $this->info(sprintf('Addon "%s" removed.', $addon_name));
    }
}
