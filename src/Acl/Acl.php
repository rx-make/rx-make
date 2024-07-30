<?php

declare(strict_types=1);

namespace RxMake\Acl;

use ReflectionAttribute;
use ReflectionEnum;
use ReflectionException;
use RuntimeException;

class Acl
{
    /**
     * Determine that $permission can accept $target.
     *
     * @param string|BasePermission $permission Base permission enum value or its identifier
     * @param string|BasePermission $target Permission enum value or its identifier to check $permission can accept
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public static function canAccept(string|BasePermission $permission, string|BasePermission $target): bool
    {
        if ($permission instanceof BasePermission) {
            $permission = self::getIdentifier($permission);
        }
        else {
            self::validateIdentifier($permission);
        }
        if ($target instanceof BasePermission) {
            $target = self::getIdentifier($target);
        }
        else {
            self::validateIdentifier($target);
        }

        [ $permissionName, $permissionRange ] = explode('@', $permission, 2);
        [ $targetName, $targetRange ] = explode('@', $target, 2);
        if (str_starts_with($permissionName, $targetName)) {
            if ($targetRange === 'All') {
                return true;
            }
            return $permissionRange === $targetRange;
        }
        return false;
    }

    /**
     * Determine if the identifier is valid.
     *
     * @param string $identifier
     *
     * @return true
     */
    public static function validateIdentifier(string $identifier): true
    {
        [ $permissionName, $permissionRange ] = explode('@', $identifier, 2);
        if (str_contains($permissionRange, '@')) {
            throw new RuntimeException('Identifier ' . $identifier . ' is not a valid permission identifier');
        }
        $segments = explode('.', $permissionName);
        foreach ($segments as $segment) {
            if (!is_subclass_of($segment, BasePermission::class)) {
                throw new RuntimeException(
                    'Identifier ' . $identifier . ' is not a valid permission identifier'
                );
            }
        }
        return true;
    }

    /**
     * Get permission value as string for identification.
     *
     * @param BasePermission $permission Permission enum value
     *
     * @return string Identifier of the permission value
     *
     * @throws ReflectionException
     */
    public static function getIdentifier(BasePermission $permission): string
    {
        $reversed = [ $permission::class ];
        while (true) {
            $parent = self::getParentPermission($reversed[0]);
            if ($parent === null) {
                break;
            }
            $reversed[] = $parent;
        }

        return implode('.', $reversed) . '@' . $permission->name;
    }

    /**
     * Get parent permission from $permissionEnum.
     *
     * @param class-string<BasePermission> $permissionEnum Enum name of permission
     *
     * @return class-string<BasePermission>|null Enum name of parent permission
     *
     * @throws ReflectionException
     */
    public static function getParentPermission(string $permissionEnum): string|null
    {
        if ($permissionEnum === GlobalPermission::class) {
            return null;
        }

        if (!class_exists($permissionEnum) || !is_subclass_of($permissionEnum, BasePermission::class)) {
            throw new RuntimeException(
                'Cannot find valid parent permission, permissions must be subclass of ' . BasePermission::class
            );
        }

        $reflection = new ReflectionEnum($permissionEnum);
        $attributeReflections = $reflection->getAttributes(AsSubPermission::class, ReflectionAttribute::IS_INSTANCEOF);
        $attributeReflection = $attributeReflections[0] ?? null;
        if ($attributeReflection === null) {
            return GlobalPermission::class;
        }

        /** @var AsSubPermission $attribute */
        $attribute = $attributeReflection->newInstance();
        return $attribute->parentPermission;
    }
}
