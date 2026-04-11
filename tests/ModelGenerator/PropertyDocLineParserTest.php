<?php

declare(strict_types=1);

namespace Tests\ModelGenerator;

use PHPUnit\Framework\TestCase;
use Zxin\Think\Model\ModelGenerator\PropertyDocLineParser;

class PropertyDocLineParserTest extends TestCase
{
    public function testParsesArrayGenericUnionWithComment(): void
    {
        $line = '@property array<string, mixed>|null $content 结构化内容（ai-config 以此为真源）';
        $r    = PropertyDocLineParser::parse($line);
        self::assertNotNull($r);
        self::assertSame('property', $r['head']);
        self::assertSame('array<string, mixed>|null', $r['type']);
        self::assertSame('content', $r['name']);
        self::assertSame('结构化内容（ai-config 以此为真源）', $r['comment']);
    }

    public function testParsesPropertyReadWithAlignedSpaces(): void
    {
        $line = '@property-read string               $status_text  状态文本';
        $r    = PropertyDocLineParser::parse($line);
        self::assertNotNull($r);
        self::assertSame('property-read', $r['head']);
        self::assertSame('string', $r['type']);
        self::assertSame('status_text', $r['name']);
        self::assertSame('状态文本', $r['comment']);
    }

    public function testParsesUnionWithSpacesInType(): void
    {
        $line = '@property string | null $maybe';
        $r    = PropertyDocLineParser::parse($line);
        self::assertNotNull($r);
        self::assertSame('string | null', $r['type']);
        self::assertSame('maybe', $r['name']);
    }

    public function testParsesAdminRoleExtExample(): void
    {
        $line = '@property array<string, mixed>|null $ext          自定义扩展字段';
        $r    = PropertyDocLineParser::parse($line);
        self::assertNotNull($r);
        self::assertSame('array<string, mixed>|null', $r['type']);
        self::assertSame('ext', $r['name']);
        self::assertSame('自定义扩展字段', $r['comment']);
    }

    public function testParsesCommentContainingDollar(): void
    {
        $line = '@property string $note price is $5';
        $r    = PropertyDocLineParser::parse($line);
        self::assertNotNull($r);
        self::assertSame('string', $r['type']);
        self::assertSame('note', $r['name']);
        self::assertSame('price is $5', $r['comment']);
    }

    public function testReturnsNullForInvalidLine(): void
    {
        self::assertNull(PropertyDocLineParser::parse('@property'));
        self::assertNull(PropertyDocLineParser::parse('not a property'));
    }

    public function testParsesWithoutComment(): void
    {
        $line = '@property int $id';
        $r    = PropertyDocLineParser::parse($line);
        self::assertNotNull($r);
        self::assertSame('int', $r['type']);
        self::assertSame('id', $r['name']);
        self::assertNull($r['comment']);
    }
}
