<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator\Options;

use function Zxin\Arr\array_map_with_key;

class MappingConfigOptions
{
    public function __construct(
        protected int $index,
        protected string $connect,
        protected string $namespace,
        protected string|array|null $table,
        protected string|null $baseClass,
        protected array|null $exclude,
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
    public static function fromArrSet(array $set, ?DefaultConfigOptions $defaultOptions = null): array
    {
        return array_map_with_key(
            fn ($item, $index) => MappingConfigOptions::fromArray($item, $index, $defaultOptions),
            $set,
        );
    }

    public static function fromArray(array $config, int $index, ?DefaultConfigOptions $defaultOptions): self
    {
        return new self(
            index: $index,
            connect: $config['connect'] ?? $defaultOptions->getConnect(),
            namespace: $config['namespace'],
            table: $config['table'] ?? null,
            baseClass: $config['baseClass'] ?? null,
            exclude: $config['exclude'] ?? null,
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

    public function getConnect(): string
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

    public function getExclude(): ?array
    {
        return $this->exclude;
    }

    public function testMatchOption(string $table, ?string $connectName): bool
    {
        $mappingConnect = $this->connect;

        if (null !== $connectName && $connectName !== $mappingConnect) {
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

    public function testExcludeOption(string $table, string $connectName): bool
    {
        if ($connectName !== $this->connect) {
            return false;
        }

        $exclude = $this->exclude;

        if (empty($exclude)) {
            return false;
        }

        return \in_array($table, $exclude, true);
    }
}
