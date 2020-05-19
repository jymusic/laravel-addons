<?php

namespace JYmusic\LaravelAddons;

use JYmusic\LaravelAddons\Generators\Php\Constant;
use JYmusic\LaravelAddons\Generators\Php\ClassName;
use JYmusic\LaravelAddons\Generators\FileGenerator;

class Generator
{
    public function __construct() {
        $this->DEFAULTS = [
            'namespace' => '',
            'directories' => [
                // path
            ],
            'files' => [
                // path
            ],
            'paths' => [
                // role => path
            ],
            'providers' => [
                // class
            ],
            'commands' => [
                // class
            ],
            'middleware' => [
                // name => class
            ],
            'routes' => [
                // domain => string
                // prefix => string
                // namespace => string
                // middleware => array
                // files => array
            ],
            'aliases' => [
                // name => class
            ],
            'includes_global_aliases' => true,
        ];
    }

    /**
     * @param string $path
     * @param string $type
     * @param array  $properties
     */
    public function generateAddon($path, $type, array $properties)
    {
        $generator = FileGenerator::make($path, __DIR__.'/../stubs/'.$type);

        $method = 'generate'.studly_case($type);

        call_user_func([$this, $method], $generator, $properties);
    }

    /**
     * 最小的插件
     *
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateMinimum(FileGenerator $generator, array $properties)
    {
        $this->generateAddonConfig($generator, $properties['namespace'], [
            'namespace' => new Constant('__NAMESPACE__'),
        ]);
    }

    /**
     * 简单插件
     *
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateSimple(FileGenerator $generator, array $properties)
    {
        $generator->directory('app', function ($generator) use ($properties) {
            $generator->directory('Providers')
                ->file('AddonServiceProvider.php')->template('AddonServiceProvider.php', $properties);
            $generator->directory('Providers')
                ->file('RouteServiceProvider.php')->template('RouteServiceProvider.php', $properties);

            $generator->keepDirectory('Controllers');

            $generator->keepDirectory('Services');
        });

        $generator->keepDirectory('config');

        $this->generateLang($generator, $properties, function ($generator) use ($properties) {
            $generator->gitKeepFile();
        });

        $generator->keepDirectory('views');

        $generator->phpBlankFile('helpers.php');
        $generator->phpBlankFile('routes.php');

        $this->generateAddonConfig($generator, $properties['namespace'], [
            'namespace' => new Constant('__NAMESPACE__'),
            'directories' => [
                'app',
            ],
            'files' => [
                'helpers.php',
            ],
            'paths' => [
                'config' => 'config',
                'lang' => 'lang',
                'views' => 'views',
            ],
            'providers' => [
                new ClassName('Providers\AddonServiceProvider'),
                new ClassName('Providers\RouteServiceProvider'),
            ],
            'http' => [
                'middlewares' => [
                ],
                'route_middlewares' => [
                ],
            ],
            'routes' => [
                'domain' => new Constant("env('APP_ADDON_DOMAIN')"),
                'prefix' => new Constant("env('APP_ADDON_PATH', '".$properties['addon_name']."')"),
                'middleware' => [],
                'files' => [
                    'routes.php'
                ],
            ],
        ], $this->DEFAULTS);
    }

    /**
     * 资源插件
     *
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateAsset(FileGenerator $generator, array $properties)
    {
        $generator->directory('assets', function ($generator) use ($properties) {
        });

        $generator->file('gulpfile.js')->template('gulpfile.js', $properties);

        $this->generateAddonConfig($generator, $properties['namespace'], [
            'paths' => [
                'assets' => 'assets',
            ],
        ]);
    }

    /**
     * 插件包 无 UI
     *
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateLibrary(FileGenerator $generator, array $properties)
    {
        $generator->directory('app', function ($generator) use ($properties) {
            $migration_class = $properties['addon_class'].'_1_0';

            $generator->directory('Providers')
                ->file('AddonServiceProvider.php')->template('AddonServiceProvider.php', $properties);
            $generator->directory('Providers')
                ->file('DatabaseServiceProvider.php')->template('DatabaseServiceProvider.php', array_merge($properties, ['migration_class_name' => $migration_class]));

            $generator->keepDirectory('Commands');

            $generator->directory('Migrations')
                ->file($migration_class.'.php')->template('Migration.php', array_merge($properties, ['class_name' => $migration_class]));
            $generator->keepDirectory('Seeds');

            $generator->keepDirectory('Services');
        });

        $generator->keepDirectory('config');

        $this->generateLang($generator, $properties, function ($generator) use ($properties) {
            $generator->phpConfigFile('messages.php', []);
        });

        $generator->directory('tests', function ($generator) use ($properties) {
            $generator->file('TestCase.php')->template('TestCase.php', $properties);
        });

        $generator->phpBlankFile('helpers.php');

        $this->generateAddonConfig($generator, $properties['namespace'], [
            'namespace' => new Constant('__NAMESPACE__'),
            'directories' => [
                'classes',
            ],
            'files' => [
                'helpers.php',
            ],
            'paths' => [
                'config' => 'config',
                'lang' => 'lang',
                'tests' => 'tests',
            ],
            'providers' => [
                new ClassName('Providers\AddonServiceProvider'),
                new ClassName('Providers\DatabaseServiceProvider'),
            ],
        ], $this->DEFAULTS);
    }

    /**
     * API 插件
     *
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateApi(FileGenerator $generator, array $properties)
    {
        $generator->directory('app', function ($generator) use ($properties) {
            $generator->directory('Providers')
                ->file('AddonServiceProvider.php')->template('AddonServiceProvider.php', $properties);
            $generator->directory('Providers')
                ->file('RouteServiceProvider.php')->template('RouteServiceProvider.php', $properties);

            $generator->keepDirectory('Commands');

            $generator->directory('Controllers')
                ->file('Controller.php')->template('Controller.php', $properties);
            $generator->keepDirectory('Middleware');

            $generator->keepDirectory('Services');
        });

        $generator->keepDirectory('config');

        $this->generateLang($generator, $properties, function ($generator) use ($properties) {
            $generator->phpConfigFile('messages.php', []);
            $generator->phpConfigFile('vocabulary.php', []);
            $generator->phpConfigFile('methods.php', []);
        });

        $generator->directory('specs')->phpConfigFile('methods.php', []);

        $generator->directory('tests', function ($generator) use ($properties) {
            $generator->file('TestCase.php')->template('TestCase.php', $properties);
        });

        $generator->phpBlankFile('helpers.php');
        $generator->file('routes.php')->template('routes.php', $properties);

        $this->generateAddonConfig($generator, $properties['namespace'], [
            'namespace' => new Constant('__NAMESPACE__'),
            'directories' => [
                'app',
            ],
            'files' => [
                'helpers.php',
            ],
            'paths' => [
                'config' => 'config',
                'lang' => 'lang',
                'specs' => 'specs',
                'tests' => 'tests',
            ],
            'providers' => [
                new ClassName('Providers\AddonServiceProvider'),
                new ClassName('Providers\RouteServiceProvider'),
            ],
            'http' => [
                'middlewares' => [
                ],
                'route_middlewares' => [
                ],
            ],
            'routes' => [
                'domain' => new Constant("env('APP_ADDON_DOMAIN')"),
                'prefix' => new Constant("env('APP_ADDON_PATH', '".lcfirst($properties['addon_name'])."')"),
                'middleware' => ['api'],
                'files' => [
                    'routes.php'
                ],
            ],
        ], $this->DEFAULTS);
    }

    /**
     * 带 UI 插件
     *
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateUi(FileGenerator $generator, array $properties)
    {
        $generator->directory('app', function ($generator) use ($properties) {
            $migration_class = $properties['addon_class'].'_1_0';

            $generator->templateDirectory('Controllers', $properties);
            $generator->keepDirectory('Middleware');

            $generator->templateDirectory('Providers', array_merge($properties, ['migration_class_name' => $migration_class]));

            $generator->keepDirectory('Services');
        });

        $generator->keepDirectory('config');

        $generator->keepDirectory('assets');

        $this->generateLang($generator, $properties, function ($generator) use ($properties) {
            $generator->phpConfigFile('messages.php', []);
            $generator->phpConfigFile('vocabulary.php', []);
            $generator->phpConfigFile('forms.php', []);
        });

        $generator->directory('specs')->phpConfigFile('forms.php', []);

        $generator->templateDirectory('views', $properties);

        $generator->templateDirectory('tests', $properties);

        $generator->phpBlankFile('helpers.php');
        $generator->templateFile('routes.php', $properties);

        $this->generateAddonConfig($generator, $properties['namespace'], [
            'namespace' => new Constant('__NAMESPACE__'),
            'directories' => [
                'app',
            ],
            'files' => [
                'helpers.php',
            ],
            'paths' => [
                'config' => 'config',
                'assets' => 'assets',
                'lang' => 'lang',
                'views' => 'views',
                'tests' => 'tests',
            ],
            'providers' => [
                new ClassName('Providers\AddonServiceProvider'),
                new ClassName('Providers\RouteServiceProvider'),
            ],
            'http' => [
                'middlewares' => [
                ],
                'route_middlewares' => [
                ],
            ],
            'routes' => [
                'domain' => new Constant("env('APP_ADDON_DOMAIN')"),
                'prefix' => new Constant("env('APP_ADDON_PATH', '".lcfirst($properties['addon_name'])."')"),
                'namespace' => new Constant("__NAMESPACE__.'\\Controllers'"),
                'middleware' => ['web'],
                'files' => [
                    'routes.php'
                ],
            ],
        ], $this->DEFAULTS);
    }

    /**
     * 简单 UI 插件
     *
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateUiSample(FileGenerator $generator, array $properties)
    {
        $generator->directory('app', function ($generator) use ($properties) {
            $migration_class = $properties['addon_class'].'_1_0';

            $generator->templateDirectory('Controllers', $properties);
            $generator->keepDirectory('Middleware');

            $generator->templateDirectory('Providers', array_merge($properties, ['migration_class_name' => $migration_class]));

            $generator->keepDirectory('Services');
        });

        $generator->keepDirectory('config');

        $generator->keepDirectory('assets');

        $this->generateLang($generator, $properties, function ($generator) use ($properties) {
            $generator->phpConfigFile('messages.php', []);
            $generator->phpConfigFile('vocabulary.php', []);
            $generator->phpConfigFile('forms.php', []);
        });

        $generator->directory('lang/en')->file('messages.php')->template('lang/en-messages.php', $properties);

        if (in_array('ja', $properties['languages'])) {
            $generator->directory('lang/zh-CN')->file('messages.php')->template('lang/zh-CN-messages.php', $properties);
        }

        $generator->directory('specs')->phpConfigFile('forms.php', []);

        $generator->templateDirectory('views', $properties);

        $generator->templateDirectory('tests', $properties);

        $generator->phpBlankFile('helpers.php');
        $generator->templateFile('routes.php', $properties);

        $this->generateAddonConfig($generator, $properties['namespace'], [
            'namespace' => new Constant('__NAMESPACE__'),
            'directories' => [
                'app',
            ],
            'files' => [
                'helpers.php',
            ],
            'paths' => [
                'config' => 'config',
                'assets' => 'assets',
                'lang' => 'lang',
                'specs' => 'specs',
                'views' => 'views',
                'tests' => 'tests',
            ],
            'providers' => [
                new ClassName('Providers\AddonServiceProvider'),
                new ClassName('Providers\RouteServiceProvider'),
            ],
            'http' => [
                'middlewares' => [
                ],
                'route_middlewares' => [
                ],
            ],
            'routes' => [
                'domain' => new Constant("env('APP_ADDON_DOMAIN')"),
                'prefix' => new Constant("env('APP_ADDON_PATH', '".lcfirst($properties['addon_name'])."')"),
                'namespace' => new Constant("__NAMESPACE__.'\\Controllers'"),
                'middleware' => ['web'],
                'files' => [
                    'routes.php'
                ],
            ],
        ], $this->DEFAULTS);
    }

    /**
     * 有管理有台的插件
     *
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateAdmin(FileGenerator $generator, array $properties)
    {
        $generator->directory('app', function ($generator) use ($properties) {
            $migration_class = $properties['addon_class'].'_1_0';

            $generator->templateDirectory('Controllers', $properties);
            $generator->keepDirectory('Middleware');

            $generator->templateDirectory('Providers', array_merge($properties, ['migration_class_name' => $migration_class]));

            $generator->keepDirectory('Services');
        });

        $generator->keepDirectory('config');

        $generator->keepDirectory('assets');

        $this->generateLang($generator, $properties, function ($generator) use ($properties) {
            $generator->phpConfigFile('messages.php', []);
            $generator->phpConfigFile('vocabulary.php', []);
            $generator->phpConfigFile('forms.php', []);
        });

        $generator->directory('lang/en')->file('messages.php')->template('lang/en-messages.php', $properties);

        if (in_array('zh-CN', $properties['languages'])) {
            $generator->directory('lang/zh-CN')->file('messages.php')->template('lang/zh-CN-messages.php', $properties);
        }

        $generator->templateDirectory('views', $properties);

        $generator->templateDirectory('tests', $properties);

        $generator->templateDirectory('routes', $properties);

        $generator->phpBlankFile('helpers.php');

        // $generator->templateFile('routes.php', $properties);

        $this->generateAddonConfig($generator, $properties['namespace'], [
            'namespace' => new Constant('__NAMESPACE__'),
            'directories' => [
                'app',
            ],
            'files' => [
                'helpers.php',
            ],
            'paths' => [
                'config' => 'config',
                'assets' => 'assets',
                'lang' => 'lang',
                'views' => 'views',
                'tests' => 'tests',
            ],
            'providers' => [
                new ClassName('Providers\AddonServiceProvider'),
                new ClassName('Providers\RouteServiceProvider'),
            ],
            'http' => [
                'middlewares' => [
                ],
                'route_middlewares' => [
                ],
            ],
            'routes' => [
                'domain' => new Constant("env('APP_ADDON_DOMAIN')"),
                'prefix' => new Constant("env('APP_ADDON_PATH', '".lcfirst($properties['addon_name'])."')"),
                'namespace' => new Constant("__NAMESPACE__.'\\Controllers'"),
                'middleware' => ['web'],
                'files' => [
                    'routes\web.php',

                ],
            ],
            'admin_routes' => [
                'domain' => new Constant("env('APP_ADDON_DOMAIN')"),
                'prefix' => new Constant("config('admin.route.prefix', '') . '/addons/" .lcfirst($properties['addon_name']). "'") ,
                'namespace' => new Constant("__NAMESPACE__.'\\Controllers'"),
                'middleware' => [
                    'web','admin'
                ],
                'files' => [
                    'routes\admin.php',
                ],
            ],
        ], $this->DEFAULTS);
    }

    /**
     * @param FileGenerator $generator
     * @param array $properties
     */
    protected function generateGenerator(FileGenerator $generator, array $properties)
    {
        $generator->directory('app', function ($generator) use ($properties) {
            $generator->directory('Providers')
                ->file('AddonServiceProvider.php')->template('AddonServiceProvider.php', $properties);
        });

        $generator->directory('config', function ($generator) use ($properties) {
            $generator->file('commands.php')->template('commands.php', $properties);
        });

        $generator->templateDirectory('stubs');

        $this->generateAddonConfig($generator, $properties['namespace'], [
            'namespace' => new Constant('__NAMESPACE__'),
            'directories' => [
                'app',
            ],
            'paths' => [
                'config' => 'config',
            ],
            'providers' => [
                new ClassName('Providers\AddonServiceProvider'),
            ],
        ]);
    }

    /**
     * 生成语言文件
     *
     * @param FileGenerator $generator
     * @param array $properties
     * @param callable $callable
     */
    protected function generateLang(FileGenerator $generator, array $properties, callable $callable)
    {
        $generator->directory('lang', function ($generator) use ($properties, $callable) {
            foreach ($properties['languages'] as $lang) {
                $generator->directory($lang, $callable);
            }
        });
    }

    /**
     * 生成插件配置文件
     *
     * @param FileGenerator $generator
     * @param $namespace
     * @param array $data
     * @param array|null $defaults
     */
    protected function generateAddonConfig(FileGenerator $generator, $namespace, array $data, array $defaults = null)
    {
        if ($defaults !== null) {
            $data = array_replace($defaults, $data);
        }

        $generator->phpConfigFile('addon.php', $data, $namespace);

        $generator->file('app.json')->json([
            'title'  => '',
            'cover' => '',
            'version' => '1.0.0',
            'fontIcon' => 'fa fa-puzzle-piece',
            'description' => '',
			'setting' => []
        ]);
    }
}
