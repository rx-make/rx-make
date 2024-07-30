<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Console\Commands;

use RxMake\Console\Command;
use RxMake\Console\Commands\Stubs\GenerateModule\GenerateModuleStub;
use RxMake\Console\Utils\Stub;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'generate:module',
    description: 'Generate a new module that is compatible with RxMake'
)]
class GenerateModuleCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            name: 'identifier',
            mode: InputArgument::REQUIRED,
            description: 'Identifier of the module'
        );
        $this->addArgument(
            name: 'name',
            mode: InputArgument::OPTIONAL,
            description: 'Human-readable name of the module',
        );

        $this->addUsage('HelloWorld');
        $this->addUsage('HelloWorld "RxMake Example"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        $identifier = ucfirst($input->getArgument('identifier'));
        $name = $input->getArgument('name') ?? $identifier;

        $rootNamespace = $this->getRootNamespace();
        if (!$rootNamespace) {
            return 1;
        }

        $moduleDir = ROOT_DIR . '/src/Modules/' . $identifier;
        if (file_exists($moduleDir)) {
            $io?->error('Path ' . $moduleDir . ' has been already in used');
            return 1;
        }
        if (!mkdir($moduleDir)) {
            $io?->error('Directory ' . $moduleDir . ' cannot be created');
            return 1;
        }

        $stub = new GenerateModuleStub(
            identifier: $identifier,
            name: $name,
            namespace: trim($rootNamespace, '\\') . '\\Modules\\' . $identifier,
        );
        try {
            $handler = new Stub($stub);
            $handler->execute($moduleDir);
            $io?->success('Module ' . $identifier . ' has been successfully generated');
            return 0;
        }
        catch (Throwable $e) {
            $io?->error($e->getMessage());
            return 1;
        }
    }

    private function getRootNamespace(): string|false
    {
        $io = $this->getIO();

        $composerJsonPath = ROOT_DIR . '/composer.json';
        if (!file_exists($composerJsonPath)) {
            $io?->error('Cannot find composer.json file');
            return false;
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath));
        $psr4 = $composerJson?->autoload?->{'psr-4'};
        if (!$psr4) {
            $io?->error('Cannot find PSR-4 autoload definition from composer.json');
            return false;
        }

        $namespace = '';
        foreach ($psr4 as $currentNamespace => $value) {
            if ($value === 'src' || $value === 'src/') {
                $namespace = $currentNamespace;
                break;
            }
        }
        if (!$namespace) {
            $io?->error('Cannot find PSR-4 namespace that has been mapped to src/');
            return false;
        }

        return $namespace;
    }
}
