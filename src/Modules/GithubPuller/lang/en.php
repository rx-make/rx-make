<?php /** @noinspection PhpUndefinedVariableInspection */

declare(strict_types=1);

namespace RxMake\Modules\GithubPuller;

use RxMake\Facade\Lang;

Lang::register($lang, __NAMESPACE__, [
    'module_info' => [
        'name' => 'GithubPuller',
        'description' => 'Listen to the GitHub webhook event to automatically execute the `git pull` command.',
    ],
    'admin' => [
        'basic_configuration' => [
            'title' => 'Basic configuration',
            'secret_key' => [
                'title' => 'Secret key',
                'description' => 'GitHub 에서 전송받은 POST 본문을 검증할 비밀 키를 입력해 주세요.',
                'randomize' => 'Randomize',
            ]
        ]
    ],
]);
