<?php

namespace Zxin\Think\Auth\Record;

use Throwable;

class RecordHelper
{
    public static function accessLog(): RecordContext
    {
        $app = app();

        if (!$app->has(RecordContext::class)) {
            $ctx = new RecordContext();
            $app->instance(RecordContext::class, $ctx);
        }

        return $app->get(RecordContext::class);
    }

    public static function recordInfo(int $code, string $message): RecordContext
    {
        return self::accessLog()->setCode($code)->setMessage($message);
    }

    public static function recordException(Throwable $throwable): RecordContext
    {
        return self::accessLog()->setException($throwable);
    }
}
