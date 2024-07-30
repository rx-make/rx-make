<?php

declare(strict_types=1);

namespace RxMake\Acl;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Rhymix\Framework\Exceptions\DBError;
use Rhymix\Framework\Exceptions\NotPermitted;

class AclGuard
{
    private static AclStatus $status;

    /**
     * Guard if the $aclStatus is not acceptable by $permission.
     *
     * @param string|BasePermission $permission Base permission enum value or its identifier
     * @param AclStatus|null        $aclStatus  Acl status instance or null to use default instance
     *
     * @return true
     * @throws DBError
     * @throws NotPermitted
     * @throws ReflectionException
     */
    public static function guard(string|BasePermission $permission, AclStatus|null $aclStatus = null): true
    {
        if ($aclStatus === null) {
            if (!isset(self::$status)) {
                self::$status = AclStatus::fromGlobals();
            }
            $aclStatus = self::$status;
        }

        $memberPermissions = $aclStatus->getPermissions();
        foreach ($memberPermissions as $memberPermission) {
            if (Acl::canAccept($permission, $memberPermission)) {
                return true;
            }
        }
        throw new NotPermitted();
    }

    /**
     * Guard if the $aclStatus does not have $identifier.
     *
     * @param string         $identifier
     * @param AclStatus|null $aclStatus
     *
     * @return true
     * @throws NotPermitted
     */
    public static function guardByIdentifier(string $identifier, AclStatus|null $aclStatus = null): true
    {
        if ($aclStatus === null) {
            if (!isset(self::$status)) {
                self::$status = AclStatus::fromGlobals();
            }
            $aclStatus = self::$status;
        }

        $identifiers = $aclStatus->getMemberIdentifiers();
        if (in_array($identifier, $identifiers)) {
            return true;
        }
        throw new NotPermitted();
    }

    /**
     * Guard properties of $object.
     *
     * @param object         $object
     * @param AclStatus|null $aclStatus
     *
     * @return object
     * @throws ReflectionException
     * @throws DBError
     */
    public static function guardProperty(object $object, AclStatus|null $aclStatus = null): object
    {
        $reflection = new ReflectionClass($object::class);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $attributeReflections = $property->getAttributes(
                AsGuardedProperty::class,
                ReflectionAttribute::IS_INSTANCEOF
            );
            $attributeReflection = $attributeReflections[0] ?? null;
            if ($attributeReflection !== null) {
                /** @var AsGuardedProperty $attribute */
                $attribute = $attributeReflection->newInstance();
                try {
                    self::guard($attribute->permission, $aclStatus);
                }
                catch (NotPermitted) {
                    unset($object->{$property->getName()});
                }
            }

            $attributeReflections = $property->getAttributes(
                AsGuardedPropertyByIdentifier::class,
                ReflectionAttribute::IS_INSTANCEOF
            );
            $attributeReflection = $attributeReflections[0] ?? null;
            if ($attributeReflection !== null) {
                /** @var AsGuardedPropertyByIdentifier $attribute */
                $attribute = $attributeReflection->newInstance();
                if (!isset($object->{$attribute->targetPropertyName})) {
                    unset($object->{$property->getName()});
                    continue;
                }
                try {
                    self::guardByIdentifier($object->{$attribute->targetPropertyName}, $aclStatus);
                }
                catch (NotPermitted) {
                    unset($object->{$property->getName()});
                }
            }
        }
        return $object;
    }
}
