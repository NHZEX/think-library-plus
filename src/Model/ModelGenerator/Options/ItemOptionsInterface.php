<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator\Options;

interface ItemOptionsInterface
{
    public static function fromArrSet(array $set, ?DefaultConfigOptions $defaultOptions = null): array;
    public static function fromArray(array $config, int $index, ?DefaultConfigOptions $defaultOptions): self;

    public function getTable(): mixed;

    public function getConnect(): string;

    public function getBaseClass(): ?string;
}
