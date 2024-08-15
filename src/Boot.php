<?php

declare(strict_types=1);

namespace RxMake;

use Context;
use Exception;
use RxMake\Console\Application;
use RxMake\Environment\Environment;
use RxMake\Module\Events\ShutdownEvent;

class Boot
{
    private static bool $afterContextInitFunctionsCalled = false;
    private static array $afterContextInitFunctions = [];

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
        self::registerShutdownEvent();
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
        define('RXMAKE_REQUEST_METHOD', $httpMethod);

        if ($httpMethod !== 'POST' && $httpMethod !== 'PUT' && $httpMethod !== 'PATCH') {
            return;
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $segments = explode('/', $_SERVER['REQUEST_URI']);
        $segments = array_slice($segments, 3);
        $route = '/' . trim(implode('/', $segments), '/');
        define('RXMAKE_ROUTE', $route);
    }

    /**
     * Register shutdown function and publish the event.
     *
     * @return void
     */
    private static function registerShutdownEvent(): void
    {
        register_shutdown_function(function () {
            (new ShutdownEvent())->publish('before');

            session_write_close();
            ignore_user_abort(true);
            fastcgi_finish_request();
            set_time_limit(0);

            (new ShutdownEvent())->publish('after');
        });
    }

    /**
     * Register $function to execute after Context::init() called.
     * If Context::init() has been already called, execute the $function immediately.
     *
     * @param callable $function
     *
     * @return void
     */
    public static function registerAfterContextInitFunction(callable $function): void
    {
        if (self::$afterContextInitFunctionsCalled) {
            $function();
            return;
        }

        if (count(self::$afterContextInitFunctions) === 0) {
            /**
             * ### EVIL HACKING ###
             * Context::init() calls Rhymix\Framework\Mobile::isFromMobilePhone() on very late time.
             * And the method isFromMobilePhone() calls base64_encode_urlsafe() function.
             * The code below injects some logics when the base64_encode_urlsafe() called.
             */
            eval(
            'namespace Rhymix\Framework {
                function base64_encode_urlsafe(string $str): string {
                    \RxMake\Boot::callAfterContextInit();
                    return strtr(rtrim(base64_encode($str), "="), "+/", "-_");
                }
            }'
            );
        }
        self::$afterContextInitFunctions[] = $function;
    }

    /**
     * Execute the registered functions by registerAfterContextInitFunction().
     *
     * @internal
     * @return void
     */
    public static function callAfterContextInit(): void
    {
        if (self::$afterContextInitFunctionsCalled) {
            return;
        }
        array_walk(self::$afterContextInitFunctions, function (callable $function) {
           $function();
        });
        self::$afterContextInitFunctionsCalled = true;
    }
}
