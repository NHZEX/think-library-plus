<?php

declare(strict_types=1);

namespace Zxin\Think\Route\Annotation;

use Zxin\Think\Annotation\BaseAnnotation;

abstract class Base extends BaseAnnotation
{
    /**
     * 预留给 dump 转存使用
     */
    public static function __set_state(array $an_array): object
    {
        /** @phpstan-ignore-next-line */
        return new static(...$an_array);
    }
}
