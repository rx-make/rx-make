<?php

declare(strict_types=1);

namespace RxMake\Module;

use BaseObject;
use ModuleController;
use ModuleHandler;
use RuntimeException;
use RxMake\Traits\MapperConstructor;

abstract class BaseModuleTrigger
{
    use MapperConstructor;

    private bool $published = false;

    /**
     * Publish trigger.
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
        return ModuleHandler::triggerCall(static::class, $position, $data);
    }

    /**
     * Listen to the trigger.
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
        ModuleController::getInstance()->addTriggerFunction(static::class, $position, $listener);
    }
}
