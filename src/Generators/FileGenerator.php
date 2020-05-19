<?php

namespace JYmusic\LaravelAddons\Generators;

use stdClass;
use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;

class FileGenerator
{
    /**
     * Output directory filesystem.
     *
     * @var \League\Flysystem\FilesystemInterface
     */
    protected $outbox;

    /**
     * Stub directory filessytem.
     *
     * @var \League\Flysystem\FilesystemInterface
     */
    protected $stubbox;

    /**
     * Context of generate.
     *
     * @var \stdClass
     */
    protected $context;

    /**
     * Create file generator.
     *
     * @param string $outbox_root_path
     * @param string $stubbox_root_path
     *
     * @return static
     */
    public static function make($outbox_root_path, $stubbox_root_path)
    {
        if (!is_dir($outbox_root_path)) {
            mkdir($outbox_root_path, 0755, true);
        }

        $outbox = FilesystemFactory::local($outbox_root_path);

        $stubbox = FilesystemFactory::local($stubbox_root_path);

        $context = (object) [
            'outbox_root' => $outbox_root_path,
            'stubbox_root' => $stubbox_root_path,
            'directory' => null,
            'file' => null,
        ];

        return new static($outbox, $stubbox, $context);
    }

    /**
     * the Constructor.
     *
     * @param \Legue\Flysystem\FilesystemInterface $outbox
     * @param \Legue\Flysystem\FilesystemInterface $stubbox
     * @param \stdClass                            $context
     */
    public function __construct(FilesystemInterface $outbox, FilesystemInterface $stubbox, stdClass $context)
    {
        $this->outbox = $outbox;
        $this->stubbox = $stubbox;
        $this->context = $context;
    }

    /**
     * Get directory walker.
     *
     * @param string   $path
     * @param callable $callable
     *
     * @return static
     */
    public function directory($path, callable $callable = null)
    {
        $directory_path = $this->makePath($path);

        $this->outbox->createDir($directory_path);

        $context = clone($this->context);
        $context->directory = $directory_path;

        $sub = new static($this->outbox, $this->stubbox, $context);

        if ($callable) {
            call_user_func($callable, $sub);
        }

        return $sub;
    }

    /**
     * Generate sources.
     *
     * @param string $path
     */
    public function sourceDirectory($path)
    {
        foreach ($this->allFiles($this->stubbox, $this->makePath($path)) as $stubbox_path) {
            if ($this->context->directory) {
                $outbox_path = substr($stubbox_path, strlen($this->context->directory) + 1);
            } else {
                $outbox_path = $stubbox_path;
            }
            $this->directory(dirname($outbox_path))->file(basename($outbox_path))->source($stubbox_path);
        }
    }

    /**
     * Generate sources from templates.
     *
     * @param string $path
     * @param array $arguments
     */
    public function templateDirectory($path, array $arguments = [])
    {
        foreach ($this->allFiles($this->stubbox, $this->makePath($path)) as $stubbox_path) {
            if ($this->context->directory) {
                $outbox_path = substr($stubbox_path, strlen($this->context->directory) + 1);
            } else {
                $outbox_path = $stubbox_path;
            }
            $this->directory(dirname($outbox_path))->file(basename($outbox_path))->template($stubbox_path, $arguments);
        }
    }

    /**
     * Generate blank directory with '.gitkeep'.
     *
     * @param string $path
     * @param string $file
     */
    public function keepDirectory($path, $file = '.gitkeep')
    {
        $this->directory($path)->gitKeepFile($file);
    }

    /**
     * Set file path.
     *
     * @param string $path
     * @return $this
     */
    public function file($path)
    {
        $this->context->file = $this->makePath($path);

        return $this;
    }

    /**
     * Check file existing.
     *
     * @param string $path
     * @return bool
     */
    public function exists($path)
    {
        return $this->outbox->has($this->makePath($path));
    }

    /**
     * Generate blank file.
     */
    public function blank()
    {
        $this->outbox->put($this->context->file, '');
    }

    /**
     * Generate text file.
     *
     * @param string $content
     * @param array $arguments
     */
    public function text($content, array $arguments = [])
    {
        $this->outbox->put($this->context->file, $arguments ? $this->generate($content, $arguments) : $content);
    }

    /**
     * Generate json file.
     *
     * @param array $data
     */
    public function json(array $data)
    {
        $this->outbox->put($this->context->file, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Generate source file from stub.
     *
     * @param string $stub_path
     */
    public function source($stub_path)
    {
        $this->outbox->put($this->context->file, $this->read($stub_path));
    }

    /**
     * Generate source file from template.
     *
     * @param string $stub_path
     * @param array $arguments
     */
    public function template($stub_path, array $arguments = [])
    {
        $this->outbox->put($this->context->file, $this->generate($this->read($stub_path), $arguments));
    }

    /**
     * Generate '.gitkeep' file.
     *
     * @param string $path
     */
    public function gitKeepFile($path = '.gitkeep')
    {
        $this->file($path)->blank();
    }

    /**
     * Generate PHP blank file.
     *
     * @param string $path
     */
    public function phpBlankFile($path)
    {
        $this->file($path)->text('<?php'.PHP_EOL.PHP_EOL);
    }

    /**
     * Generate PHP config file.
     *
     * @param string $path
     * @param string $namespace
     */
    public function phpConfigFile($path, array $config = [], $namespace = null)
    {
        $this->file($path)->text(Php\ConfigGenerator::generateText($config, $namespace));
    }

    /**
     * Generate PHP source file.
     *
     * @param string $path
     * @param string $source
     * @param string $namespace
     */
    public function phpSourceFile($path, $source, $namespace = '')
    {
        if ($namespace) {
            $namespace = "namespace {$namespace};".PHP_EOL.PHP_EOL;
        }

        $this->file($path)->text('<?php'.PHP_EOL.PHP_EOL.$namespace.$source.PHP_EOL);
    }

    /**
     * Generate source file, same name.
     *
     * @param string $path
     */
    public function sourceFile($path)
    {
        $this->file($path)->source($this->makePath($path));
    }

    /**
     * Generate template file, same name.
     *
     * @param string $path
     * @param array $arguments
     */
    public function templateFile($path, array $arguments = [])
    {
        $this->file($path)->template($this->makePath($path), $arguments);
    }

    /**
     * Create relative path in box.
     *
     * @param $path
     * @return string
     */
    protected function makePath($path)
    {
        return $this->context->directory ? $this->context->directory.'/'.$path : $path;
    }

    /**
     * Get all files in directory
     *
     * @param FilesystemInterface $filesystem
     * @param string $path
     * @return array
     */
    protected function allFiles(FilesystemInterface $filesystem, $path)
    {
        $files = [];

        foreach ($filesystem->listContents($path, true) as $file) {
            if ($file['type'] == 'file') {
                $files[] = $file['path'];
            }
        }

        return $files;
    }

    /**
     * Generate template
     *
     * @param string $content
     * @param array $arguments
     * @return mixed
     */
    protected function generate($content, array $arguments)
    {
        foreach ($arguments as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $content = preg_replace('/\{\s*\$'.$name.'\s*\}/', $value, $content);
        }

        return $content;
    }

    /**
     * Read stub file
     *
     * @param string $content
     * @param array $arguments
     * @return mixed
     */
    protected function read($stub_path)
    {
        $content = $this->stubbox->read($stub_path);

        if ($content === false) {
            throw new InvalidArgumentException("File '$stub_path' is not found.");
        }

        return $content;
    }
}
