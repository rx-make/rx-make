<?php

declare(strict_types=1);

namespace RxMake\Console\Utils;

use RuntimeException;
use Throwable;

class Stub
{
    private BaseStubDefinition $definition;
    private array $vars;

    public function __construct(BaseStubDefinition $definition)
    {
        $this->definition = $definition;
        $this->vars = $definition->getVars();
    }

    public function execute(string $target): void
    {
        if (!is_dir($target)) {
            throw new RuntimeException('Cannot find directory: ' . $target);
        }

        $this->definition->configure();
        $structure = $this->definition->getStructure();
        $directories = array_filter($structure, function ($item) {
            return $item['type'] === 'directory';
        });
        $files = array_filter($structure, function ($item) {
            return $item['type'] === 'file';
        });

        $succeed = [];
        try {
            foreach ($directories as $dir) {
                $dirName = $this->compileString($dir['name']);
                $targetDir = $target . '/' . $dirName;
                if (file_exists($targetDir)) {
                    throw new RuntimeException('Directory ' . $targetDir . ' already exists');
                }
                mkdir($targetDir, recursive: true);
                $succeed[] = $targetDir;
            }
            foreach ($files as $file) {
                $fileName = $this->compileString($file['name']);
                $targetDir = $target . '/' . $fileName;
                if (file_exists($targetDir)) {
                    throw new RuntimeException('File ' . $targetDir . ' already exists');
                }
                $compiled = $this->compileString(file_get_contents($file['stub']));
                file_put_contents($targetDir, $compiled);
                $succeed[] = $targetDir;
            }
        }
        catch (Throwable $e) {
            $this->rollback($succeed);
            throw new RuntimeException($e->getMessage());
        }
    }

    private function rollback(array $succeed): void
    {
        foreach ($succeed as $dir) {
            if (is_dir($dir)) {
                $files = array_diff(scandir($dir), ['.', '..']);
                $this->rollback(array_map(function ($item) use ($dir) {
                    return $dir . '/' . $item;
                }, $files));
                rmdir($dir);
                continue;
            }
            unlink($dir);
        }
    }

    private function compileString(string $string): string
    {
        foreach ($this->vars as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            $string = str_replace('{{ $' . $key . ' }}', $value, $string);
        }
        return $string;
    }
}
