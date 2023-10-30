<?php
declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator\Data;

class RecordRow
{
    public function __construct(
        private string $connect,
        private string $table,
        private string $className,
        private string $filename,
        private string $status,
        private string $content = '',
        private bool $change = false,
    )
    {
    }

    public function getConnect(): string
    {
        return $this->connect;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getFilename(?string $cutRootPath = null): string
    {
        if ($cutRootPath) {
            return \str_starts_with($this->filename, $cutRootPath) ? \substr($this->filename, \strlen($cutRootPath)) : $this->filename;
        } else {
            return $this->filename;
        }
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function isChange(): bool
    {
        return $this->change;
    }
}
