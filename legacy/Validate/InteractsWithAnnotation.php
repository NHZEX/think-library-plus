<?php

namespace Zxin\Think\Validate;

use think\Validate;
use Zxin\Think\Validate\Annotation\Validation;

/**
 * Trait InteractsWithAnnotation
 * @package Zxin\Think\Validate
 * @property string $namespace
 */
trait InteractsWithAnnotation
{
    public function parseAnnotation(string $class, string $method): ?Validation
    {
        try {
            $refClass = new \ReflectionClass($class);
        } catch (\ReflectionException) {
            return null;
        }
        if ($refClass->isAbstract() || $refClass->isTrait()) {
            return null;
        }
        try {
            $refMethod = $refClass->getMethod($method);
        } catch (\ReflectionException) {
            return null;
        }
        if (!$refMethod->isPublic() || $refMethod->isStatic()) {
            return null;
        }
        $methodName = $refMethod->getName();
        if (str_starts_with($methodName, '_')) {
            return null;
        }
        $annotations = [];
        foreach ($refMethod->getAttributes(Validation::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $annotations[] = $attribute->newInstance();
        }
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Validation) {
                $result = $this->parseValidation($annotation, $method);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        return null;
    }

    public function parseValidation(Validation $validation, string $method): ?Validation
    {
        if (empty($validation->name)) {
            return null;
        }
        if (str_starts_with($validation->name, '@')) {
            $class = $this->namespace . str_replace('.', '\\', substr($validation->name, 1));
        } elseif (class_exists($validation->name)) {
            $class = $validation->name;
        } elseif (str_starts_with($validation->name, '\\')) {
            // 弃用的写法
            $class = $validation->name;
        } else {
            return null;
        }
        if (!class_exists($class) || !is_subclass_of($class, Validate::class)) {
            return null;
        }
        $validation = clone $validation;
        $validation->name = $class;
        if ($validation->scene === '_') {
            $validation->scene = $method;
        }
        return $validation;
    }
}
