<?php /** @noinspection PhpUndefinedVariableInspection */

declare(strict_types=1);

namespace RxMake\Modules\Dummy;

use RxMake\Facade\Lang;

Lang::register($lang, __NAMESPACE__, [
    'module_info' => [
        'name' => 'Dummy',
        'description' => 'Module Dummy from RxMake\Modules\Dummy',
    ],
]);
