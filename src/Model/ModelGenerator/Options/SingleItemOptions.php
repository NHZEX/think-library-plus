<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator\Options;

use function Zxin\Arr\array_map_with_key;

class SingleItemOptions implements ItemOptionsInterface
{
    public function __construct(
        protected int     $index,
        protected string  $class,
        protected string  $table,
        protected string  $connect,
        protected ?string $baseClass,
        protected ?bool   $fieldToCamelCase,
    ) {
        if (-1 > $index) {
            throw new \InvalidArgumentException('index must be greater than -1');
        }
    }

    /**
     * @return array<SingleItemOptions>
     */
    public static function fromArrSet(array $set, ?DefaultConfigOptions $defaultOptions = null): array
    {
        return array_map_with_key(
            fn ($item, $index) => SingleItemOptions::fromArray($item, $index, $defaultOptions),
            $set,
        );
    }

    public static function fromArray(array $config, int $index, ?DefaultConfigOptions $defaultOptions): self
    {
        return new self(
            index: $index,
            class: $config['class'],
            table: $config['table'],
            connect: $config['connect'] ?? $defaultOptions->getConnect(),
            baseClass: $config['baseClass'] ?? $defaultOptions->getBaseClass(),
            fieldToCamelCase: $config['fieldToCamelCase'] ?? $defaultOptions->isFieldToCamelCase(),
        );
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getConnect(): string
    {
        return $this->connect;
    }

    public function getBaseClass(): ?string
    {
        return $this->baseClass;
    }

    public function isFieldToCamelCase(): ?bool
    {
        return $this->fieldToCamelCase;
    }
}
