<?php

declare(strict_types=1);

namespace Zxin\Think\Auth\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Auth extends Base
{
    public function __construct(
        // 定义权限分配
        public string $name = 'login',
        // 定义权限策略
        public string $policy = ''
    ) {
    }
}
