<?php /** @noinspection PhpUndefinedVariableInspection */

declare(strict_types=1);

namespace RxMake\Modules\GithubPuller;

use RxMake\Facade\Lang;

Lang::register($lang, __NAMESPACE__, [
    'module_info' => [
        'name' => 'GithubPuller',
        'description' => 'GitHub Webhook　イヴェントを受信して　`git pull`　コマンドお自動的に実行します。',
    ],
    'admin' => [
        'basic_configuration' => [
            'title' => '基本設定',
            'secret_key' => [
                'title' => '秘密キー',
                'description' => 'GitHubから送信されたPOST本文を検証する秘密キーを入力してください。',
                'randomize' => 'ランダム生成',
            ]
        ]
    ],
]);
