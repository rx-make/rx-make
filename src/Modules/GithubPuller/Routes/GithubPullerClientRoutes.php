<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Modules\GithubPuller\Routes;

use FastRoute\ConfigureRoutes;
use RxMake\Module\BaseModuleRoutes;
use RxMake\Modules\GithubPuller\Controllers\Client\GithubPullerClientWebhookController;

class GithubPullerClientRoutes extends BaseModuleRoutes
{
    public function routes(ConfigureRoutes $r): void
    {
        $r->post('/webhook', [
            GithubPullerClientWebhookController::class,
            'handle'
        ]);
    }
}
