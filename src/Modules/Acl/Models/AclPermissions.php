<?php

declare(strict_types=1);

namespace RxMake\Modules\Acl\Models;

use DateTime;
use RxMake\Database\BaseModel;

class AclPermissions extends BaseModel
{
    public const string TableName = 'rxmake_modules_acl_permissions';

    /**
     * @var int Primary serial number.
     */
    public int $primarySrl;

    /**
     * @var 'member'|'group' Target type of permission.
     */
    public string $targetType;

    /**
     * @var int Target serial number such as member_srl or group_srl.
     */
    public int $targetSrl;

    /**
     * @var string Permission identifier from \RxMake\Acl\Acl::getIdentifier
     */
    public string $permissionIdentifier;

    /**
     * @var DateTime Created datetime.
     */
    public DateTime $createdAt;

    /**
     * @var DateTime|null Updated datetime.
     */
    public DateTime|null $updatedAt;
}
