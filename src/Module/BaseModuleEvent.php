<?php

declare(strict_types=1);

namespace RxMake\Module;

use BaseObject;
use ModuleController;
use ModuleHandler;
use RuntimeException;
use RxMake\Traits\MapperConstructor;

abstract class BaseModuleEvent
{
    use MapperConstructor;

    private bool $published = false;

    /**
     * Publish the event.
     *
     * @param 'before'|'after' $position
     *
     * @return BaseObject
     */
    public function publish(string $position): BaseObject
    {
        if ($this->published) {
            throw new RuntimeException('the trigger has been already published');
        }
        if ($position !== 'before' && $position !== 'after') {
            throw new RuntimeException('$position must be one of "before" or "after"');
        }

        $this->published = true;
        return ModuleHandler::triggerCall(self::getTriggerName(), $position, $this);
    }

    /**
     * Listen to the event.
     *
     * @param 'before'|'after' $position
     * @param callable         $listener
     *
     * @return void
     */
    public static function listen(string $position, callable $listener): void
    {
        if ($position !== 'before' && $position !== 'after') {
            throw new RuntimeException('$position must be one of "before" or "after"');
        }
        ModuleController::getInstance()->addTriggerFunction(self::getTriggerName(), $position, $listener);
    }

    /**
     * Get Rhymix trigger name of the event.
     *
     * @return string
     */
    public static function getTriggerName(): string
    {
        return trim(static::class, '\\');
    }
}
