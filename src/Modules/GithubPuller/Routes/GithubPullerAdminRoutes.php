<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Modules\GithubPuller\Routes;

use FastRoute\ConfigureRoutes;
use RxMake\Module\BaseModuleRoutes;
use RxMake\Modules\GithubPuller\Controllers\Admin\GithubPullerAdminConfigController;

class GithubPullerAdminRoutes extends BaseModuleRoutes
{
    public function routes(ConfigureRoutes $r): void
    {
        $r->get('/', [ GithubPullerAdminConfigController::class, 'view' ]);
        $r->post('/', [ GithubPullerAdminConfigController::class, 'save' ]);
    }
}
