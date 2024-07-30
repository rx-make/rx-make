<?php

declare(strict_types=1);

namespace RxMake\Acl;

use Attribute;
use RuntimeException;

#[Attribute(Attribute::TARGET_CLASS)]
class AsSubPermission
{
    public function __construct(public string $parentPermission)
    {
        if (!class_exists($parentPermission) || !is_subclass_of($parentPermission, BasePermission::class)) {
            throw new RuntimeException(
                'Cannot find valid parent permission, permissions must be subclass of ' . BasePermission::class
            );
        }
    }
}
