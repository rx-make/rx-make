<?php

declare(strict_types=1);

namespace RxMake\Acl;

/**
 * Special permission that will be treated as root permission.
 */
enum GlobalPermission implements BasePermission
{
    case All;
    case Create;
    case Read;
    case Update;
    case Delete;
}
