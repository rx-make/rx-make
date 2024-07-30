<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Console\Commands;

use RuntimeException;
use RxMake\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'init',
    description: 'Initialize Rhymix core and RxMake',
)]
class InitCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        try {
            $this->checkPublicExists();
            $io?->info('There\'s no problem to initialize Rhymix core and RxMake');

            $this->updateGitSubmodule();
            $io?->info('Rhymix core has been initialized as git submodule');

            $returnCode = $this->getApplication()?->doRun(new ArrayInput([
                'command' => 'update',
            ]), $output);
            if ($returnCode !== null && $returnCode !== 0) {
                return $returnCode;
            }
            $io?->success('Rhymix core and RxMake has been successfully initialized');
            return 0;
        }
        catch (Throwable $e) {
            $io?->error($e->getMessage());
            return 1;
        }
    }

    private function checkPublicExists(): void
    {
        if (!file_exists(RHYMIX_DIR)) {
            return;
        }

        if (!is_dir(RHYMIX_DIR)) {
            throw new RuntimeException(
                'There\'s some unknown file named `public`. This directory name is reserved for rhymix.'
            );
        }

        $rhymixComposerJsonPath = RHYMIX_DIR . '/common/composer.json';
        if (!file_exists($rhymixComposerJsonPath)) {
            throw new RuntimeException(
                'There\'s some unknown directory named `public`. This directory name is reserved for rhymix.'
            );
        }

        $rhymixComposerJson = json_decode(file_get_contents($rhymixComposerJsonPath));
        if ($rhymixComposerJson?->name !== 'rhymix/rhymix') {
            throw new RuntimeException(
                'There\'s some unknown directory named `public`. This directory name is reserved for rhymix.'
            );
        }

        throw new RuntimeException(
            'Rhymix core has been detected on directory named `public`. If you really want to execute the command, delete it first.'
        );
    }

    private function updateGitSubmodule(): void
    {
        $submoduleOutput = shell_exec('git submodule update --init');
        if (
            $submoduleOutput === null
            || $submoduleOutput === false
            || trim($submoduleOutput) === ''
            || !str_contains($submoduleOutput, 'Submodule path \'public\': checked out')
            || !is_dir(RHYMIX_DIR)
        ) {
            throw new RuntimeException('Rhymix initialization has been failed by an unexpected error.',);
        }
    }
}
