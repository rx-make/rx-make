<?php /** @noinspection PhpClassCanBeReadonlyInspection */

declare(strict_types=1);

namespace RxMake\Console\Utils;

use RuntimeException;

class Composer
{
    public function __construct(
        private readonly string $vendorDir
    ) {}

    /**
     * Get closest PSR-4 namespace and its mapping directory.
     *
     * @param string $target Target to search.
     *
     * @return array{
     *     namespace: string,
     *     directory: string,
     * }|null
     */
    public function getClosestPsr4Namespace(string $target): array|null
    {
        $target = trim($target, '\\') . '\\';

        $composerPsr4DefinitionPath = $this->vendorDir . '/composer/autoload_psr4.php';
        if (!file_exists($composerPsr4DefinitionPath)) {
            throw new RuntimeException('Cannot find /vendor/composer/autoload_psr4.php');
        }
        $psr4DefinitionArr = require $composerPsr4DefinitionPath;
        if (!is_array($psr4DefinitionArr)) {
            throw new RuntimeException('Found /vendor/composer/autoload_psr4.php does not return an array');
        }

        $closestNamespace = null;
        $closestNamespaceLength = 0;
        foreach (array_keys($psr4DefinitionArr) as $namespace) {
            if (!str_starts_with($target, $namespace)) {
                continue;
            }
            $namespaceLength = count(explode('\\', $namespace));
            if ($closestNamespaceLength < $namespaceLength) {
                $closestNamespace = $namespace;
                $closestNamespaceLength = $namespaceLength;
            }
        }

        return $closestNamespace ? [
            'namespace' => $closestNamespace,
            'directory' => $psr4DefinitionArr[$closestNamespace],
        ] : null;
    }

    /**
     * Get a directory that has been bound to the target vendor name.
     *
     * @param string $target Vendor name to search.
     *
     * @return string|null
     */
    public function getSpecificVendorDirectory(string $target): string|null
    {
        $specificVendorDir = $this->vendorDir . '/' . $target;
        if (!is_dir($specificVendorDir)) {
            return null;
        }
        return $specificVendorDir;
    }
}
