<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Modules\Dummy\Routes;

use FastRoute\ConfigureRoutes;
use RxMake\Module\BaseModuleRoutes;

class DummyClientRoutes extends BaseModuleRoutes
{
    public function routes(ConfigureRoutes $r): void
    {
        $r->get('/', function () {
            $this->setRelativeTemplatePath('Views');
            $this->setTemplateFile('Empty.blade.php');
        });
    }
}
