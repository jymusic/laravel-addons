<?php

namespace JYmusic\LaravelAddons\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use JYmusic\LaravelAddons\Environment as AddonEnvironment;
use JYmusic\LaravelAddons\Addon;
use UnexpectedValueException;

class AddonNameCommand extends Command
{
    use Functions;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'addon:name
        {addon : The desired addon.}
        {namespace : The desired namespace.}
        {--force : Force remove.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the addon PHP namespace';

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Jumilla\Addomnipot\Laravel\Addons\Addon
     */
    protected $addon;

    /**
     * @var string
     */
    protected $currentNamespace;

    /**
     * @var string
     */
    protected $newNamespace;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Filesystem $filesystem, AddonEnvironment $env)
    {
        $this->filesystem = $filesystem;

        $addon_name = $this->argument('addon');

        $this->addon = $env->addon($addon_name);

        // check addon
        if ($this->addon === null) {
            throw new UnexpectedValueException("Addon '$addon_name' is not found.");
        }

        $this->currentNamespace = trim($this->addon->phpNamespace(), '\\');

        $this->newNamespace = str_replace('/', '\\', $this->argument('namespace'));

        // check namespace
        if (! $this->validPhpNamespace($this->newNamespace)) {
            throw new UnexpectedValueException("PHP namespace '{$this->newNamespace}' is invalid.");
        }

        // confirm
        $this->line('Addon name: '.$addon_name);
        $this->line('Addon path: '.$this->addon->relativePath($this->laravel));
        $this->line('PHP namespace: '.$this->newNamespace);

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure? [y/N]', false)) {
                $this->comment('canceled');

                return;
            }
        }

        $this->setAddonNamespaces();

        $this->setComposerNamespace();

        $this->setClassNamespace();

        $this->setConfigNamespaces();

        $this->info('Addon namespace set!');
    }

    /**
     * Set the namespace in addon.php, adon.json file.
     */
    protected function setAddonNamespaces()
    {
        $this->setAddonConfigNamespaces();
        $this->setAddonJsonNamespaces();
    }

    /**
     * Set the namespace in addon.php file.
     */
    protected function setAddonConfigNamespaces()
    {
        if (file_exists($this->addon->path('addon.php'))) {
            $search = [
                "namespace {$this->currentNamespace}",
                "'namespace' => '{$this->currentNamespace}'",
                "'{$this->currentNamespace}\\",
                "\"{$this->currentNamespace}\\",
                "\\{$this->currentNamespace}\\",
            ];

            $replace = [
                "namespace {$this->newNamespace}",
                "'namespace' => '{$this->newNamespace}'",
                "'{$this->newNamespace}\\",
                "\"{$this->newNamespace}\\",
                "\\{$this->newNamespace}\\",
            ];

            $this->replaceIn($this->addon->path('addon.php'), $search, $replace);
        }
    }

    /**
     * Set the namespace in addon.json file.
     */
    protected function setAddonJsonNamespaces()
    {
        if (file_exists($this->addon->path('addon.json'))) {
            $currentNamespace = str_replace('\\', '\\\\', $this->currentNamespace);
            $newNamespace = str_replace('\\', '\\\\', $this->newNamespace);

            $search = [
                "\"namespace\": \"{$currentNamespace}\"",
                "\"{$currentNamespace}\\\\",
                "\\\\{$currentNamespace}\\\\",
            ];

            $replace = [
                "\"namespace\": \"{$newNamespace}\"",
                "\"{$newNamespace}\\\\",
                "\\\\{$newNamespace}\\\\",
            ];

            $this->replaceIn($this->addon->path('addon.json'), $search, $replace);
        }
    }

    /**
     * Set the PSR-4 namespace in the Composer file.
     */
    protected function setComposerNamespace()
    {
        if (file_exists($this->addon->path('composer.json'))) {
            $this->replaceIn(
                $this->addon->path('composer.json'), $this->currentNamespace.'\\\\', str_replace('\\', '\\\\', $this->newNamespace).'\\\\'
            );
        }
    }

    /**
     * Set the namespace on the files in the class directory.
     */
    protected function setClassNamespace()
    {
        $classDirectories = $this->addon->config('addon.directories', []);

        if (count($this->addon->config('addon.directories', [])) === 0) {
            return;
        }

        $files = Finder::create();

        foreach ($classDirectories as $path) {
            $files->in($this->addon->path($path));
        }

        $files->name('*.php');

        $search = [
            $this->currentNamespace.'\\',
            'namespace '.$this->currentNamespace.';',
        ];

        $replace = [
            $this->newNamespace.'\\',
            'namespace '.$this->newNamespace.';',
        ];

        foreach ($files as $file) {
            $this->replaceIn($file, $search, $replace);
        }
    }

    /**
     * Set the namespace in the appropriate configuration files.
     */
    protected function setConfigNamespaces()
    {
        $configPath = $this->addon->path($this->addon->config('paths.config', 'config'));

        if ($this->filesystem->isDirectory($configPath)) {
            $files = Finder::create()
                ->in($configPath)
                ->name('*.php');

            foreach ($files as $file) {
                $this->replaceConfigNamespaces($file->getRealPath());
            }
        }
    }

    /**
     * Replace the namespace in PHP configuration file.
     *
     * @param string $path
     */
    protected function replaceConfigNamespaces($path)
    {
        $search = [
            "'{$this->currentNamespace}\\",
            "\"{$this->currentNamespace}\\",
            "\\{$this->currentNamespace}\\",
        ];

        $replace = [
            "'{$this->newNamespace}\\",
            "\"{$this->newNamespace}\\",
            "\\{$this->newNamespace}\\",
        ];

        $this->replaceIn($path, $search, $replace);
    }

    /**
     * Replace the given string in the given file.
     *
     * @param string $path
     * @param string | array $search
     * @param string | array $replace
     */
    protected function replaceIn($path, $search, $replace)
    {
        if ($this->output->isVerbose()) {
            $this->line("{$path} ...");
        }

        $this->filesystem->put($path, str_replace($search, $replace, $this->filesystem->get($path)));
    }
}
