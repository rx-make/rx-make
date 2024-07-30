<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Modules\Dummy\EventHandlers;

use RxMake\Module\BaseModule;

class GetModuleListInSitemapEventHandler extends BaseModule
{
    public function handleAfter(array &$array): void
    {
        $array[] = 'rxmake_modules_dummy';
    }
}
