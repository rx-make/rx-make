<?php

declare(strict_types=1);

namespace RxMake\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Application extends BaseApplication
{
    private ConsoleIO|null $io = null;

    public function __construct()
    {
        parent::__construct('RxMake', MAKE_VERSION);
        $this->registerCommands();
    }

    public function registerCommands(): void
    {
        $commandFiles = scandir(__DIR__ . '/Commands');
        $commandFiles = array_diff($commandFiles, ['.', '..']);
        foreach ($commandFiles as $commandFile) {
            if (is_dir(__DIR__ . '/Commands/' . $commandFile)) {
                continue;
            }
            $commandClassName = 'RxMake\\Console\\Commands\\' . substr($commandFile, 0, -4);
            $this->add(new $commandClassName());
        }
    }

    public function getIO(): ConsoleIO|null
    {
        return $this->io;
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $exitCode = parent::run($input, $output);
        $output?->write("\n");
        return $exitCode;
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new ConsoleIO($input, $output);
        try {
            $exitCode = parent::doRun($input, $output);
        }
        catch (Throwable $e) {
            $this->io->error($e->getMessage());
            $exitCode = 1;
        }
        return $exitCode;
    }
}
