<?php

declare(strict_types=1);

namespace RxMake\Acl;

use Context;
use Rhymix\Framework\Cache;
use Rhymix\Framework\Exceptions\DBError;
use RxMake\Database\Filter;
use RxMake\Modules\Acl\Models\AclPermissions;

class AclStatus
{
    /**
     * @var int Member serial number.
     */
    private int $memberSrl;

    /**
     * @var string Member email address.
     */
    private string $emailAddress;

    /**
     * @var array<int> Group serial numbers.
     */
    private array $groupSrls;

    /**
     * @var bool True if the member is super admin.
     */
    private bool $isAdmin;

    /**
     * @var bool True if all permissions have been loaded from DB or cache.
     */
    private bool $loaded = false;

    /**
     * @var array<string> Permission list of current request.
     */
    private array $permissions = [];

    /**
     * Constructor.
     *
     * @param object $member Rhymix user object.
     */
    public function __construct(object $member)
    {
        $this->memberSrl = $member->member_srl ?? 0;
        $this->emailAddress = $member->email_address ?? '';
        $this->groupSrls = array_keys($member->group_list ?? []);
        $this->isAdmin = ($member->is_admin ?? 'N') === 'Y';

        if ($this->isAdmin) {
            $this->permissions = [ GlobalPermission::All ];
            $this->loaded = true;
        }
    }

    /**
     * Get member identifiers.
     *
     * @return string[]
     */
    public function getMemberIdentifiers(): array
    {
        return [
            $this->memberSrl,
            $this->emailAddress,
            ...$this->groupSrls,
        ];
    }

    /**
     * Get all permissions.
     * If permissions have not been loaded, will load permissions first.
     *
     * @return array<string>
     *
     * @throws DBError
     */
    public function getPermissions(): array
    {
        if (!$this->loaded) {
            $this->load();
        }
        return $this->permissions;
    }

    /**
     * Load all permissions from DB or cache.
     *
     * @param bool $fromCache If true, load permissions from cache if it's available.
     *
     * @return void
     *
     * @throws DBError
     */
    public function load(bool $fromCache = true): void
    {
        $this->permissions = [
            ...$this->getPermissionsFromMember($fromCache),
            ...$this->getPermissionsFromGroup($fromCache),
        ];
        sort($this->permissions);
        $this->loaded = true;
    }

    /**
     * @throws DBError
     */
    private function getPermissionsFromMember(bool $fromCache): array
    {
        if ($this->memberSrl === 0) {
            return [];
        }
        $cacheKey = self::class . '.member_' . $this->memberSrl;
        if ($fromCache) {
            $output = Cache::get($cacheKey);
            if ($output !== null) {
                return $output;
            }
        }

        $output = AclPermissions::find(fn (Filter $f) => $f
            ->eq('targetType', 'member')
            ->eq('targetSrl', $this->memberSrl)
        );
        $memberPermissions = array_map(function (AclPermissions $permissions) {
            return $permissions->permissionIdentifier;
        }, $output);
        Cache::set($cacheKey, $memberPermissions);
        return $memberPermissions;
    }

    /**
     * @throws DBError
     */
    private function getPermissionsFromGroup(bool $fromCache): array
    {
        if (count($this->groupSrls) === 0) {
            return [];
        }
        $permissions = [];
        foreach ($this->groupSrls as $groupSrl) {
            $cacheKey = self::class . '.group_' . $groupSrl;
            if ($fromCache) {
                $output = Cache::get($cacheKey);
                if ($output !== null) {
                    $permissions = array_merge($permissions, $output);
                    continue;
                }
            }
            $output = AclPermissions::find(fn (Filter $f) => $f
                ->eq('targetType', 'group')
                ->eq('targetSrl', $groupSrl)
            );
            $groupPermissions = array_map(function (AclPermissions $permissions) {
                return $permissions->permissionIdentifier;
            }, $output);
            Cache::set($cacheKey, $groupPermissions);
            $permissions = array_merge($permissions, $groupPermissions);
        }
        return $permissions;
    }

    /**
     * Create a new instance of Context data.
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        $loggedInfo = Context::get('logged_info');
        if (!$loggedInfo || !is_object($loggedInfo)) {
            $loggedInfo = (object) [
                'member_srl' => 0,
                'group_list' => [],
                'is_admin' => 'N',
            ];
        }
        return new self($loggedInfo);
    }
}
