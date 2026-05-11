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

    public function getName(): string
    {
        return $this->name;
    }

    public function addGroup(self $group): void
    {
        $this->groups[$group->getName()] = $group;
    }

    public function addRule(string $name, mixed $rule): void
    {
        $this->rules[$name] = $rule;
    }

    public function getGroup(string $name): ?self
    {
        return $this->groups[$name] ?? null;
    }

    public function getRule(string $name): mixed
    {
        return $this->rules[$name] ?? null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'groups' => array_map(static fn (self $group): array => $group->toArray(), $this->groups),
            'rules' => $this->rules,
        ];
    }
}
