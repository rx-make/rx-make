<?php

declare(strict_types=1);

namespace RxMake\Modules\GithubPuller\Models;

use RxMake\Module\BaseModuleConfig;

class GithubPullerConfig extends BaseModuleConfig
{
    /**
     * @var string Secret key to validate POST body
     */
    public string $secretKey = '';
}
