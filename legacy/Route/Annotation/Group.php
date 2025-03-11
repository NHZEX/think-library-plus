<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

/**
 * 路由分组
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Group extends Base
{
    use TOptions;

    public function __construct(
        public ?string           $name = null,
        public null|string|array $middleware = null,
        // ==== 通用参数 ====
        public ?bool             $complete_match = null,
        public ?array            $filter = null,
        public ?array            $append = null,
        public ?array            $pattern = null,
        // ==== 特殊参数 ====
        public int               $registerSort = 1000,
    ) {
    }
}
