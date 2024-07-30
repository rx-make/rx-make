<?php

declare(strict_types=1);

namespace RxMake\Acl;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AsGuardedPropertyByIdentifier
{
    public function __construct(
        public string $targetPropertyName,
    ) {}
}
