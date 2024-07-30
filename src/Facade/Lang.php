<?php

declare(strict_types=1);

namespace RxMake\Facade;

use Closure;
use RuntimeException;

class Lang
{
    /**
     * Register language values.
     *
     * @param object $lang Global lang variable.
     * @param string $namespace
     * @param array<string, string|array<string, string>> $data
     *
     * @return void
     */
    public static function register(object $lang, string $namespace, array $data): void
    {
        if (!preg_match('/[A-Za-z_\\\]+/', $namespace)) {
            throw new RuntimeException('Invalid namespace');
        }

        $namespace = trim($namespace, '\\');
        $prefix = self::getPrefixFromNamespace($namespace);

        $append = function (array $data, string $parentKey = '') use (&$append, $lang, $prefix) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $append($value, $parentKey . $key . '_');
                }
                else if (is_string($value)) {
                    $lang->{$prefix . $parentKey . $key} = $value;
                }
                else {
                    throw new RuntimeException('Invalid lang value');
                }
            }
        };
        $append($data);
    }

    /**
     * Get language value from namespace by key.
     *
     * @param string $namespace
     * @param string $key
     *
     * @return string
     */
    public static function get(string $namespace, string $key): string
    {
        $nKey = self::getPrefixFromNamespace($namespace) . $key;
        return $GLOBALS['lang']->{$nKey} ?? $nKey;
    }

    /**
     * Get closure that can retrieve a language value from preset namespace.
     *
     * @param string $namespace
     *
     * @return Closure
     */
    public static function getLangSet(string $namespace): Closure
    {
        return function (string $key) use ($namespace) {
            return self::get($namespace, $key);
        };
    }

    private static function getPrefixFromNamespace(string $namespace): string
    {
        return strtolower(
            str_replace(
                search: '\\',
                replace: '_',
                subject: trim($namespace, '\\')
            )
        ) . '_';
    }
}
