<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Console\Commands;

use RxMake\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'link:module',
    description: 'Link modules to public/modules directory',
)]
class LinkModuleCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            name: 'namespace',
            mode: InputArgument::REQUIRED,
            description: 'PSR-4 Namespace, or composer vendor name with "@" prefix, of the module to be linked'
        );

        $this->addUsage('RxMake\\Examples\\Modules\\HelloWorld\\');
        $this->addUsage('@rx-make/hello-world');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $namespace = $input->getArgument('namespace');
        if (str_starts_with($namespace, '@')) {
            return $this->executeComposerVendor($namespace);
        }
        else {
            return $this->executePsrNamespace($namespace);
        }
    }

    private function executeComposerVendor(string $prefixedVendorName): int
    {
        $io = $this->getIO();
        $vendorName = substr($prefixedVendorName, 1);

        $vendorPath = ROOT_DIR . '/vendor/' . $vendorName;
        if (!is_dir($vendorPath)) {
            $io->error('Cannot find directory for vendor: ' . $vendorName);
            return 1;
        }

        $confModuleFiles = [];
        $recursiveScanDir = function (string $dir, bool $exceptPublic = false) use (&$recursiveScanDir, &$confModuleFiles) {
            $array = scandir($dir);
            $array = array_diff($array, [ '.', '..' ]);
            if ($exceptPublic) {
                $array = array_diff($array, [ 'public' ]);
            }
            foreach ($array as $item) {
                if (!is_dir($currentDir = $dir . '/' . $item)) {
                    continue;
                }
                if ($item !== 'conf') {
                    $recursiveScanDir($currentDir);
                    continue;
                }
                $confModuleFile = $currentDir . '/module.xml';
                if (!file_exists($confModuleFile)) {
                    continue;
                }
                $confModuleFiles[] = $confModuleFile;
            }
        };
        $recursiveScanDir($vendorPath, true);

        $failed = [];
        $alreadyDone = [];
        $success = [];

        foreach ($confModuleFiles as $confModuleFile) {
            $xml = simplexml_load_file($confModuleFile);
            foreach ($xml->attributes() as $attr => $value) {
                if ($attr !== 'rxmake-compatible') {
                    continue;
                }
                if ((string) $value !== 'true') {
                    continue;
                }
                $namespaces = $xml->xpath('/module/namespaces/namespace');
                foreach ($namespaces[0]->attributes() as $attr2 => $value2) {
                    if ($attr2 !== 'name') {
                        continue;
                    }
                    $namespace = (string) $value2;
                    $moduleDir = realpath(dirname($confModuleFile) . '/../');
                    $output = LinkCommand::link(
                        $moduleDir,
                        $destination = RHYMIX_DIR . '/modules/' . strtolower(
                                str_replace('\\', '_', trim($namespace, '\\'))
                            )
                    );
                    if ($output['status'] === 'failed') {
                        $failed[] = [
                            'Source' => $moduleDir,
                            'Destination' => $destination,
                            'Status' => '<error>FAILED</>',
                        ];
                    }
                    else if ($output['status'] === 'already-done') {
                        $alreadyDone[] = [
                            'Source' => $moduleDir,
                            'Destination' => $destination,
                            'Status' => '<warning>ALREADY DONE</>',
                        ];
                    }
                    else if ($output['status'] === 'success') {
                        $success[] = [
                            'Source' => $moduleDir,
                            'Destination' => $destination,
                            'Status' => '<success>SUCCESS</>',
                        ];
                    }
                }
            }
        }

        if (empty($failed) && empty($alreadyDone) && empty($success)) {
            $io?->error('Cannot find Rxmake compatible module from vendor: ' . $vendorName);
            return 1;
        }

        if (!empty($failed) && empty($alreadyDone) && empty($success)) {
            $io?->error(count($failed) . ' modules have not been successfully linked.');
            $io?->table($failed);
            return 1;
        }

        if (empty($failed) && !empty($alreadyDone) && empty($success)) {
            $io?->info(count($alreadyDone) . ' modules have been already linked.');
            $io?->table($alreadyDone);
            return 0;
        }

        if (empty($failed) && empty($alreadyDone) && !empty($success)) {
            $io?->success(count($success) . ' modules have been successfully linked.');
            $io?->table($success);
            return 0;
        }

        if (!empty($failed) && !empty($alreadyDone) && empty($success)) {
            $io?->error(count($failed) . ' modules have not been linked, and ' . count($alreadyDone) . ' modules have been already linked.');
            $io?->table($failed);
            $io?->table($alreadyDone);
            return 1;
        }

        if (!empty($failed) && empty($alreadyDone) && !empty($success)) {
            $io?->error(count($failed) . ' modules have not been linked, and ' . count($success) . ' modules have been successfully linked.');
            $io?->table($failed);
            $io?->table($success);
            return 1;
        }

        if (empty($failed) && !empty($alreadyDone) && !empty($success)) {
            $io?->success(count($success) . ' modules have been successfully linked, and ' . count($alreadyDone) . ' modules have been already linked.');
            $io?->table($success);
            $io?->table($alreadyDone);
            return 0;
        }

        $io?->error(count($failed) . ' modules have not been linked, and ' . count($success) . ' modules have been successfully linked, and ' . count($alreadyDone) . ' modules have been already linked.');
        $io?->table($failed);
        $io?->table($success);
        $io?->table($alreadyDone);
        return 1;
    }

    private function executePsrNamespace(string $namespace): int
    {
        $io = $this->getIO();
        $namespace = trim($namespace, '\\') . '\\';

        $autoloadPsr4Path = ROOT_DIR . '/vendor/composer/autoload_psr4.php';
        $autoloadPsr4Array = require $autoloadPsr4Path;

        $closestNamespace = '';
        foreach (array_keys($autoloadPsr4Array) as $currentNamespace) {
            if (!str_starts_with($namespace, $currentNamespace)) {
                continue;
            }
            $closestNamespaceLength = count(explode('\\', $closestNamespace));
            $currentNamespaceLength = count(explode('\\', $currentNamespace));
            if ($currentNamespaceLength > $closestNamespaceLength) {
                $closestNamespace = $currentNamespace;
            }
        }

        if ($closestNamespace === '') {
            $io?->error('Cannot find directory for namespace: ' . $namespace);
            return 1;
        }

        $remainParts = substr($namespace, strlen($closestNamespace));
        $namespaceDirs = $autoloadPsr4Array[$closestNamespace];
        foreach ($namespaceDirs as $namespaceDir) {
            $moduleDir = $namespaceDir . '/' . str_replace('\\', '/', $remainParts);
            if (!is_dir($moduleDir)) {
                continue;
            }
            $confModuleFile = $moduleDir . '/conf/module.xml';
            if (!file_exists($confModuleFile)) {
                continue;
            }

            $xml = simplexml_load_file($confModuleFile);
            foreach ($xml->attributes() as $attr => $value) {
                if ($attr !== 'rxmake-compatible') {
                    continue;
                }
                if ((string) $value !== 'true') {
                    continue;
                }

                $output = LinkCommand::link(
                    $moduleDir,
                    $destination = RHYMIX_DIR . '/modules/' . strtolower(
                            str_replace('\\', '_', trim($namespace, '\\'))
                        )
                );
                if ($output['status'] === 'failed') {
                    $io?->error('Module namespace ' . $namespace . ' has not been successfully linked');
                    return 1;
                }
                else if ($output['status'] === 'already-done') {
                    $io?->info('Module namespace ' . $namespace . ' has already been linked');
                }
                else if ($output['status'] === 'success') {
                    $io?->success('Module namespace ' . $namespace . ' has been successfully linked');
                }
                $io?->table([
                    [
                        'Source' => $moduleDir,
                        'Destination' => $destination,
                    ],
                ]);
                return 0;
            }

            $io?->error('Cannot find Rxmake compatible module from namespace: ' . $namespace);
            return 1;
        }

        $io?->error('Cannot find Rxmake compatible module from namespace: ' . $namespace);
        return 1;
    }
}
