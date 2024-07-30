<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Modules\Acl\Routes;

use FastRoute\ConfigureRoutes;
use RxMake\Module\BaseModuleRoutes;

class AclAdminRoutes extends BaseModuleRoutes
{
    public function routes(ConfigureRoutes $r): void
    {
        $r->get('/', function () {
        });
    }
}
