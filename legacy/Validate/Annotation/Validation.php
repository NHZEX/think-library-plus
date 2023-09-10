<?php

declare(strict_types=1);

namespace Zxin\Think\Validate\Annotation;

use Attribute;
use Zxin\Think\Annotation\BaseAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Validation extends BaseAnnotation
{
    public function __construct(
        public string $name,
        public string $scene = '',
    ) {
    }
}
