<?php

declare(strict_types=1);

namespace RxMake\Module;

enum RouteOptions
{
    /**
     * Ignore CSRF checks.
     */
    case NoCsrfCheck;
}
