<?php

declare(strict_types=1);

namespace RxMake;

use Exception;
use RxMake\Console\Application;
use RxMake\Environment\Environment;

class Boot
{
    /**
     * Bootstrap application.
     *
     * @param string $bootstrapDir Directory that stores bootstrap.php file.
     *
     * @return void
     */
    public static function boot(string $bootstrapDir): void
    {
        self::defineConstants($bootstrapDir);
        self::registerEnvironment();
        self::registerHttpRouterRoute();
    }

    /**
     * Boot console application.
     *
     * @return void
     */
    public static function bootConsole(): void
    {
        $app = new Application();
        try {
            $app->run();
        }
        catch (Exception $e) {
            echo $e->getMessage();
            echo '\n';
            echo $e->getTraceAsString();
            exit(1);
        }
    }

    /**
     * Define constants.
     *
     * @param string $bootstrapDir
     *
     * @return void
     */
    private static function defineConstants(string $bootstrapDir): void
    {
        /**
         * RxMake version constants.
         */
        define('MAKE_VERSION', '0.0.1');

        /**
         * Path constants.
         */
        define('MAKE_DIR', __DIR__);
        define('APP_DIR', realpath($bootstrapDir));
        define('ROOT_DIR', realpath($bootstrapDir . '/..'));
        define('RHYMIX_DIR', realpath($bootstrapDir . '/../public'));

        /**
         * Rhymix blade directive helpers.
         */
        define('noescape', 1);
    }

    /**
     * Register RxMake\Environment.
     *
     * @return void
     */
    private static function registerEnvironment(): void
    {
        Environment::registerGlobals();
        Environment::injectIntoRhymix();
    }

    /**
     * Register RXMAKE_ROUTE constant.
     *
     * @return void
     */
    private static function registerHttpRouterRoute(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        if ($httpMethod !== 'POST' && !$httpMethod !== 'PUT' && $httpMethod !== 'PATCH') {
            return;
        }

        $segments = explode('/', $_SERVER['REQUEST_URI']);
        $segments = array_slice($segments, 3);
        $route = '/' . trim(implode('/', $segments), '/');
        define('RXMAKE_ROUTE', $route);
    }
}
