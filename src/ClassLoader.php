<?php

namespace JYmusic\LaravelAddons;


class ClassLoader
{
    /**
     * @var static
     */
    protected static $instance;

    /**
     * @param array $addons
     */
    public static function register(Environment $env, $addons)
    {
        static::$instance = new static($env, $addons);

        // TODO check addon configuration

        spl_autoload_register([static::$instance, 'load'], true, false);
    }

    /**
     */
    public static function unregister()
    {
        if (static::$instance) {
            spl_autoload_unregister([static::$instance, 'load']);
        }
    }

    protected $env;

    protected $addons;

    /**
     * @param Environment $env
     * @param array $addons
     */
    public function __construct(Environment $env, array $addons)
    {
        $this->env = $env;
        $this->addons = $addons;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public function load($className)
    {
        foreach ($this->addons as $addon) {
            $namespace = $addon->phpNamespace();

            $namespacePrefix = $namespace ? $namespace.'\\' : '';

            // 如果它不是附加命名空间下的类
            if (!starts_with($className, $namespacePrefix)) {
                continue;
            }

            $relativeClassName = substr($className, strlen($namespacePrefix));

            // 创建类相对路径（PSR-4）
            $relativePath = $this->env->classToPath($relativeClassName);

            // 在所有目录下搜索（PSR-4）
            foreach ($addon->config('addon.directories') as $directory) {
                $path = $addon->path($directory.'/'.$relativePath);

                if (file_exists($path)) {
                    require_once $path;

                    return true;
                }
            }
        }

        return false;
    }
}
