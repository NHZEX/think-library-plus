<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

use Nette\Utils\Validators;
use think\helper\Str;

class PropertyCollection
{
    protected int $typeStrMaxLen  = 0;
    protected int $fieldStrMaxLen = 0;

    protected int $index = 0;
    protected array $properties = [];
    protected array $refProps = [];

    public function __construct(
        protected ?bool $fieldToCamelCase,
        protected bool $alignPadding,
    ) {
    }

    public static function fromFields(iterable $fields, ?bool $fieldToCamelCase, ?bool $alignPadding = true): PropertyCollection
    {
        $self = new PropertyCollection(
            fieldToCamelCase: $fieldToCamelCase,
            alignPadding: $alignPadding,
        );

        foreach ($fields as $item) {
            $field   = $item['COLUMN_NAME'];
            $comment = $item['COLUMN_COMMENT'];

            $type = match ($item['DATA_TYPE']) {
                'bit',
                'int',
                'integer',
                'tinyint',
                'smallint',
                'mediumint' => 'int',
                'double',
                'float' => 'float',
                'bigint',
                'decimal',
                'enum',
                'text',
                'char',
                'varchar',
                'tinytext',
                'datetime',
                'binary',
                'varbinary' => 'string',
                'json' => 'array',
                default => 'mixed',
            };

            $self->push($field, $type, $comment);
        }
        return $self;
    }

    public function push(string $field, string $type, string $comment): void
    {
        $field = Str::snake($field);
        $this->properties[$field] = [
            'index'   => $this->index++,
            'field'   => $field,
            'type'    => $type,
            'comment' => $comment,
        ];

        $this->fieldStrMaxLen = max($this->fieldStrMaxLen, \strlen($field));
        $this->typeStrMaxLen  = max($this->typeStrMaxLen, \strlen($type));
    }

    public function appendRef(string $field, string $type, ?string $comment): void
    {
        $type = trim($type);
        $field = Str::snake($field);

        if (empty($type)) {
            return;
        }

        $this->refProps[$field] = [
            'type'    => $type,
            'comment' => $comment ? (trim($comment) ?: null) : null,
        ];

        $this->typeStrMaxLen  = max($this->typeStrMaxLen, \strlen($type));
    }

    public function hasProperty(string $field): bool
    {
        $field = Str::snake($field);
        return isset($this->properties[$field]);
    }

    public function resetMaxLen(): void
    {
        foreach ($this->properties as $property) {
            $this->fieldStrMaxLen = max($this->fieldStrMaxLen, \strlen($property['field']));
            $this->typeStrMaxLen  = max($this->typeStrMaxLen, \strlen($property['type']));
        }

        $this->typeStrMaxLen  = array_reduce($this->refProps, fn ($carry, $item) => max($carry, \strlen($item['type'])), $this->typeStrMaxLen);
    }

    public function outPropertyLines(): \Generator
    {
        foreach ($this->properties as $field => $property) {
            yield $this->propertyToStr($property, $this->refProps[$field] ?? null);
        }
    }

    protected function propertyToStr(array $property, ?array $refOptions): string
    {
        $field   = $property['field'];
        $type    = $property['type'];
        $comment = $property['comment'];

        if (!empty($refOptions['type']) && !Validators::isBuiltinType($refOptions['type'])) {
            $type = $refOptions['type'];
        }
        if (!empty($refOptions['comment'])) {
            $comment = $refOptions['comment'];
        }

        $printType = $this->alignPadding ? str_pad($type, $this->typeStrMaxLen) : $type;

        $line = [
            '@property',
            $printType,
            null,
            null,
        ];
        if ($this->fieldToCamelCase) {
            $field = Str::camel($field);
        }
        if ($comment) {
            $line[2] = '$' . ($this->alignPadding ? str_pad($field, $this->fieldStrMaxLen) : $field);
            $line[3] = $comment;
        } else {
            $line[2] = '$' . $field;
        }

        $line = array_filter($line);
        return join(' ', $line);
    }

    public function outputAllText(): string
    {
        $output = '';
        foreach ($this->outPropertyLines() as $line) {
            $output .= "{$line}\n";
        }
        return $output;
    }

    public function isFieldToCamelCase(): ?bool
    {
        return $this->fieldToCamelCase;
    }
}
