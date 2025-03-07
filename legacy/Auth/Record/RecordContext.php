<?php

namespace Zxin\Think\Auth\Record;

class RecordContext
{
    protected string $message = '';

    protected int $code = 0;

    protected ?array $extra = null;

    public function setMessage(string $message): RecordContext
    {
        $this->message = $message;
        return $this;
    }

    public function setCode(int $code): RecordContext
    {
        $this->code = $code;
        return $this;
    }

    public function setException(\Throwable $throwable): RecordContext
    {
        $this->code = $throwable->getCode();
        $this->message = \sprintf('%s [%s]', $throwable->getMessage(), $throwable::class);
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): void
    {
        $this->extra = $extra;
    }
}
