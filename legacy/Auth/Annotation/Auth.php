<?php

declare(strict_types=1);

namespace Zxin\Think\Auth\Annotation;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Auth extends Base
{
    /**
     * @param string|string[] $name
     */
    public function __construct(
        // 定义权限分配
        public string|array $name = 'login',
        // 功能备注
        public ?string $desc = null,
        // 定义权限策略
        public string $policy = '',
    ) {
    }
}
