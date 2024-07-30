<?php

declare(strict_types=1);

namespace RxMake\Environment;

use Dotenv\Dotenv;
use Rhymix\Framework\Config;
use RuntimeException;

class Environment
{
    private static Dotenv $dotenv;
    private static bool $rhymixInjected = false;

    public static function registerGlobals(string $envDir = ROOT_DIR, string|null $envFile = null): void
    {
        if (isset(self::$dotenv)) {
            throw new RuntimeException('Environment already registered');
        }
        self::$dotenv = Dotenv::createImmutable($envDir, $envFile);
        self::$dotenv->load();
    }

    public static function injectIntoRhymix(): void
    {
        if (!isset(self::$dotenv)) {
            throw new RuntimeException('Environment not registered');
        }
        if (self::$rhymixInjected) {
            throw new RuntimeException('Environment already injected');
        }

        /**
         * ### EVIL HACKING ###
         * This class has been called before Rhymix\Framework\Config::init() from autoload.php.
         * So there's no normal way to pre-initialize Config instance and inject the values.
         *
         * ## So how?
         * Rhymix calls Rhymix\Framework\Debug::registerErrorHandlers() after initialize the Config instance.
         * And the method, registerErrorHandler() depends on global scoped config() function.
         * With this in mind, we declared the config() function within the Rhymix\Framework namespace scope
         * to inject the value the first time it is called.
         */
        eval(
        'namespace Rhymix\Framework {
                function config(string $key, mixed $value = null) {
                    static $injected = false;
                    if (!$injected) {
                        \RxMake\Environment\Environment::setAllEnvToRhymixConfig();
                        $injected = true;                        
                    }
                    if ($value === null) {
                        return \Rhymix\Framework\Config::get($key);
                    }
                    else {
                        \Rhymix\Framework\Config::set($key, $value);                        
                    }
                } 
            }'
        );
    }

    public static function setAllEnvToRhymixConfig(): void
    {
        foreach ($_ENV as $key => $value) {
            if (!str_starts_with($key, 'RX__')) {
                continue;
            }
            if (str_contains($value, '|')) {
                $value = explode('|', $value);
            }
            else if ($value === 'true') {
                $value = true;
            }
            else if ($value === 'false') {
                $value = false;
            }
            else if (is_numeric($value)) {
                $value = (int) $value;
            }

            $key = str_replace('__',  '.', substr(strtolower($key), 4));
            Config::set($key, $value);
        }
    }
}
