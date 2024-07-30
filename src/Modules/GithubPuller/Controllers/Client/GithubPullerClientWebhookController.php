<?php

declare(strict_types=1);

namespace RxMake\Modules\GithubPuller\Controllers\Client;

use ModuleObject;
use Rhymix\Framework\Exceptions\TargetNotFound;
use RuntimeException;
use RxMake\Modules\GithubPuller\Models\GithubPullerConfig;

class GithubPullerClientWebhookController extends ModuleObject
{
    /**
     * @throws TargetNotFound
     */
    public function webhook(): void
    {
        $config = GithubPullerConfig::get();
        $secretKey = $config->secretKey;
        if (!$secretKey) {
            throw new RuntimeException('Secret key not set');
        }

        $rawBody = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'];
        if (!$this->validateWebhook($rawBody, $signature, $secretKey)) {
            throw new TargetNotFound();
        }

        $cwd = getcwd();
        shell_exec(sprintf(
            'cd %s && git pull && cd %s',
            ROOT_DIR,
            $cwd,
        ));
    }

    private function validateWebhook(string $body, string $signature, string $secretKey): bool
    {
        if (!$body || !$signature) {
            return false;
        }
        $segments = explode('=', $signature);
        if (count($segments) !== 2 || $segments[0] !== 'sha256') {
            return false;
        }
        $receivedSig = $segments[1];
        $generatedSig = hash_hmac('sha256', $body, $secretKey);
        if ($receivedSig !== $generatedSig) {
            return false;
        }
        return true;
    }
}
