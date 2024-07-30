<?php

declare(strict_types=1);

namespace RxMake\Modules\GithubPuller\Controllers\Admin;

use Context;
use Rhymix\Framework\Exceptions\DBError;
use RxMake\Module\BaseModule;
use RxMake\Modules\GithubPuller\Models\GithubPullerConfig;

class GithubPullerAdminConfigController extends BaseModule
{
    public function view(): void
    {
        $config = GithubPullerConfig::get();
        Context::set('config', $config);

        $this->setRelativeTemplatePath('Views/Admin');
        $this->setTemplateFile('ConfigView.blade.php');
    }

    /**
     * @throws DBError
     */
    public function save(): void
    {
        $config = GithubPullerConfig::get();
        $config->secretKey = Context::get('secretKey');
        GithubPullerConfig::set($config);

        $this->setValidatorMessage('info', 'success_saved');
        $this->setRedirectRoute('/');
    }
}
