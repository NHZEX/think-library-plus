<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Data;

final class RuleGroupData
{
    private array $groups = [];
    private array $rules = [];

    public function __construct(
        private string $name,
    ) {
    }
}
