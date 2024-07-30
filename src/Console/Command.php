<?php

declare(strict_types=1);

namespace RxMake\Console;

use Symfony\Component\Console\Command\Command as BaseCommand;

class Command extends BaseCommand
{
    protected function getIO(): ConsoleIO|null
    {
        /** @var Application $application */
        $application = $this->getApplication();
        return $application->getIO();
    }
}
