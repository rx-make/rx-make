<?php /** @noinspection PhpUndefinedVariableInspection */

declare(strict_types=1);

namespace RxMake\Modules\GithubPuller;

use RxMake\Facade\Lang;

Lang::register($lang, __NAMESPACE__, [
    'module_info' => [
        'name' => 'GithubPuller',
        'description' => 'GitHub 웹훅 이벤트를 수신하여 `git pull` 명령어를 자동으로 실행합니다.',
    ],
    'admin' => [
        'basic_configuration' => [
            'title' => '기본 설정',
            'secret_key' => [
                'title' => '비밀 키',
                'description' => 'GitHub 에서 전송받은 POST 본문을 검증할 비밀 키를 입력해 주세요.',
                'randomize' => '랜덤 생성',
            ]
        ]
    ],
]);
