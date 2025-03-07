<?php

declare(strict_types=1);

namespace Zxin\Think\Auth\Annotation;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AuthDesc extends Base
{
    /**
     * @param array<string, string|numeric|array{0: string, 1: number}> $desc
     */
    public function __construct(
        public array $desc,
    ) {
    }
}
