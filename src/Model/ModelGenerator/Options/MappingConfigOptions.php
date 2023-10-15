<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator\Options;

use function Zxin\Arr\array_map_with_key;

class MappingConfigOptions
{
    public function __construct(
        protected int $index,
        protected string|null $connect,
        protected string $namespace,
        protected string|array|null $table,
        protected string|null $baseClass,
    ) {
        if (-1 > $index) {
            throw new \InvalidArgumentException('index must be greater than -1');
        }
        if ($this::class === DefaultConfigOptions::class && $index !== -1) {
            throw new \InvalidArgumentException('index must be -1');
        }
    }

    /**
     * @param array $set
     * @return array<MappingConfigOptions>
     */
    public static function fromArrSet(array $set): array
    {
        return array_map_with_key(
            fn ($item, $index) => MappingConfigOptions::fromArray($item, $index),
            $set,
        );
    }

    public static function fromArray(array $config, int $index): self
    {
        return new self(
            index: $index,
            connect: $config['connect'] ?? null,
            namespace: $config['namespace'],
            table: $config['table'] ?? null,
            baseClass: $config['baseClass'] ?? null,
        );
    }

    public function isDefault(): bool
    {
        return false;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getConnect(): ?string
    {
        return $this->connect;
    }

    public function getTable(): array|string|null
    {
        return $this->table;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getBaseClass(): ?string
    {
        return $this->baseClass;
    }

    public function testMatchOption(string $table, ?string $connectName): bool
    {
        $mappingConnect = $this->connect ?? null;

        if (null !== $connectName && null !== $mappingConnect && $connectName !== $mappingConnect) {
            return false;
        }

        $matchTable = $this->table;

        if (empty($matchTable) && $connectName === $mappingConnect) {
            return true;
        }

        foreach ($matchTable as $pattern) {
            if (fnmatch($pattern, $table)) {
                return true;
            };
        }

        return false;
    }
}
