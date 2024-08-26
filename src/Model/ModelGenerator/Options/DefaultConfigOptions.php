<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator\Options;

class DefaultConfigOptions extends MappingConfigOptions
{
    public static function makeDefault(
        string $connect,
        string $namespace,
        string $baseClass,
        ?array $exclude,
        ?bool $fieldToCamelCase,
        ?bool $alignPadding,
    ): DefaultConfigOptions {
        return new DefaultConfigOptions(
            index: -1,
            connect: $connect,
            namespace: $namespace,
            table: null,
            baseClass: $baseClass,
            exclude: $exclude,
            fieldToCamelCase: $fieldToCamelCase,
            alignPadding: $alignPadding,
        );
    }

    public function isDefault(): bool
    {
        return true;
    }

    public function getConnect(): string
    {
        return $this->connect;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getBaseClass(): string
    {
        return $this->baseClass;
    }
}
