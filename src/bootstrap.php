<?php

declare(strict_types=1);

use RxMake\Boot;

require __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../public/common/autoload.php')) {
    require_once __DIR__ . '/../public/common/autoload.php';
}

Boot::boot(__DIR__);
