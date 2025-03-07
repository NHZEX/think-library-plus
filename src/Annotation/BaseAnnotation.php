<?php

declare(strict_types=1);

namespace Zxin\Think\Annotation;

abstract class BaseAnnotation
{
    /**
     * Error handler for unknown property accessor in Annotation class.
     *
     * @param string $name Unknown property name.
     *
     * @throws \BadMethodCallException
     */
    public function __get($name)
    {
        throw new \BadMethodCallException(
            \sprintf("Unknown property '%s' on annotation '%s'.", $name, static::class)
        );
    }

    /**
     * Error handler for unknown property mutator in Annotation class.
     *
     * @param string $name  Unknown property name.
     * @param mixed  $value Property value.
     *
     * @throws \BadMethodCallException
     */
    public function __set($name, mixed $value)
    {
        throw new \BadMethodCallException(
            \sprintf("Unknown property '%s' on annotation '%s'.", $name, static::class)
        );
    }
}
