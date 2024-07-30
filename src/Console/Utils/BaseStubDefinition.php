<?php

declare(strict_types=1);

namespace RxMake\Console\Utils;

use ReflectionClass;
use ReflectionProperty;

abstract class BaseStubDefinition
{
    /**
     * @var array<array{
     *     type: 'directory'|'file',
     *     name: string,
     *     stub?: string,
     * }>
     */
    private array $structure = [];

    /**
     * Configure stub definition.
     *
     * @return void
     */
    abstract public function configure(): void;

    /**
     * Get stub structure.
     *
     * @return array<array{
     *      type: 'directory'|'file',
     *      name: string,
     *      stub?: string,
     *  }>
     */
    final public function getStructure(): array
    {
        return $this->structure;
    }

    /**
     * Get variables.
     *
     * @return array<string, string>
     */
    final public function getVars(): array
    {
        $ref = new ReflectionClass($this);
        $propertyRefs = $ref->getProperties(ReflectionProperty::IS_PROTECTED);

        $vars = [];
        foreach ($propertyRefs as $propertyRef) {
            $vars[$propertyRef->getName()] = $this->{$propertyRef->getName()};
        }

        return $vars;
    }

    /**
     * Add directory to stub.
     *
     * @param string $name Directory name.
     *
     * @return void
     */
    final public function addDirectory(string $name): void
    {
        $this->structure[] = [
            'type' => 'directory',
            'name' => $name,
        ];
    }

    final public function addFile(string $name, string $stub): void
    {
        $this->structure[] = [
            'type' => 'file',
            'name' => $name,
            'stub' => $stub,
        ];
    }
}
