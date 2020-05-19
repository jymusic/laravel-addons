<?php

namespace JYmusic\LaravelAddons;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;

class AliasResolver
{
    /**
     * @var static
     */
    protected static $instance;

    /**
     * @param string $apppath
     * @param array $addons
     * @param array $aliases
     */
    public static function register($apppath, array $addons, array $aliases)
    {
        static::$instance = new static($apppath, $addons, $aliases);

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

    /**
     * @var array
     */
    protected $addons;

    /**
     * @var array
     */
    protected $globalClassAliases;

    /**
     * The constructor.
     *
     * @param string $apppath
     * @param array $addons
     * @param array $aliases
     */
    public function __construct($apppath, array $addons, array $aliases)
    {
        $this->addons = array_merge([$this->makeAppAddon($apppath)], $addons);
        $this->globalClassAliases = $aliases;
    }

    /**
     * Make addon instance for application namespace.
     *
     * @param string $path
     *
     * @return static
     */
    protected function makeAppAddon($path)
    {
        $config = [
            'namespace' => trim($this->getAppNamespace(), '\\'),
        ];

        return new Addon(null, 'app', $path, $config);
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

            // 没有命名空间的软件包不需要别名解析
            if (empty($namespace)) {
                continue;
            }

            $namespacePrefix = $namespace.'\\';
            $includesGlobalAliases = $addon->config('addon.includes_global_aliases', true);
            $addonAliases = $addon->config('addon.aliases', []);

            // 如果它不是附加命名空间下的类
            if (!starts_with($className, $namespacePrefix)) {
                continue;
            }

            // 检索类别名称
            $parts = explode('\\', $className);
            $relativeClassName = $parts[count($parts) - 1];

            // 检查全局别名
            if ($includesGlobalAliases) {
                if (isset($this->globalClassAliases[$relativeClassName])) {
                    $originalClassName = $this->globalClassAliases[$relativeClassName];
                    class_alias($originalClassName, $className);

                    return true;
                }
            }

            // 检查特定于软件包的别名
            if ($addonAliases) {
                if (isset($addonAliases[$relativeClassName])) {
                    $originalClassName = $addonAliases[$relativeClassName];
                    class_alias($originalClassName, $className);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the application namespace.
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }
}
