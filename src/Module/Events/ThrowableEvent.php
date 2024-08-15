<?php

declare(strict_types=1);

namespace RxMake\Module\Events;

use RxMake\Module\BaseModuleEvent;
use Throwable;

class ThrowableEvent extends BaseModuleEvent
{
    public Throwable $throwable;
}
