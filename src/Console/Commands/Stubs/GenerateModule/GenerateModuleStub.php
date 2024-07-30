<?php

declare(strict_types=1);

namespace RxMake\Console\Commands\Stubs\GenerateModule;

use RxMake\Console\Utils\BaseStubDefinition;

class GenerateModuleStub extends BaseStubDefinition
{
    protected string $fullyQualifiedPrefix;
    protected string $hyphenedIdentifier;

    public function __construct(protected string $identifier, protected string $name, protected string $namespace)
    {
        $this->fullyQualifiedPrefix = ucfirst(strtolower(
            str_replace('\\', '_', $namespace)
        ));
        $this->hyphenedIdentifier = strtolower(
            preg_replace('/(?<!^)[A-Z]/', '-$0', $identifier)
        );
    }

    public function configure(): void
    {
        $this->addDirectory('conf');
        $this->addDirectory('schemas');
        $this->addDirectory('lang');
        $this->addDirectory('Controllers');
        $this->addDirectory('Controllers/Client');
        $this->addDirectory('Controllers/Admin');
        $this->addDirectory('Models');
        $this->addDirectory('Routes');

        $this->addFile('conf/info.xml', __DIR__ . '/info.xml.stub');
        $this->addFile('conf/module.xml', __DIR__ . '/module.xml.stub');
        $this->addFile('lang/en.php', __DIR__ . '/Language.php.stub');
        $this->addFile('Models/{{ $identifier }}Config.php', __DIR__ . '/Config.php.stub');
        $this->addFile('Routes/{{ $identifier }}AdminRoutes.php', __DIR__ . '/AdminRoutes.php.stub');
        $this->addFile('Routes/{{ $identifier }}ClientRoutes.php', __DIR__ . '/ClientRoutes.php.stub');
    }
}
