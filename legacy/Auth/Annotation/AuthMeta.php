<?php

declare(strict_types=1);

namespace Zxin\Think\Auth\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class AuthMeta extends Base
{
    public function __construct(
        // 功能注解
        public string $desc,
        // 定义策略
        public string $policy = ''
    )
    {
    }
}
