<?php

declare(strict_types=1);

namespace RxMake\Acl;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AsGuardedProperty
{
    public function __construct(
        public BasePermission $permission
    ) {}
}
