<?php

declare(strict_types=1);

namespace RxMake\Module;

use ModuleController;
use ModuleModel;
use Rhymix\Framework\Exceptions\DBError;
use RxMake\Traits\MapperConstructor;

abstract class BaseModuleConfig
{
    use MapperConstructor;

    private static array $configs = [];

    public static function get(): static
    {
        if (!array_key_exists(static::class, self::$configs)) {
            $config = ModuleModel::getModuleConfig(static::class);
            $object = new static($config ?? []);
            self::$configs[static::class] = $object;
        }
        return self::$configs[static::class];
    }

    /**
     * @throws DBError
     */
    public static function set(self $config): true
    {
        $output = ModuleController::getInstance()->insertModuleConfig(static::class, $config);
        if (!$output->toBool()) {
            throw new DBError($output->getMessage());
        }
        self::$configs[static::class] = $config;
        return true;
    }
}
