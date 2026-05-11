<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

/**
 * 解析单行 @property / @property-read 等 PHPDoc，支持泛型、联合类型等复杂 type。
 *
 * 属性名取行内最后一次「空白 + $标识符」匹配，避免泛型/模板中的 $ 被误判为字段名。
 */
final class PropertyDocLineParser
{
    /**
     * @return array{head: string, type: string, name: string, comment: ?string}|null
     */
    public static function parse(string $line): ?array
    {
        if (!preg_match('/^@(property\S*)\s+/u', $line, $headMatch, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $head          = $headMatch[1][0];
        $typeStartByte = $headMatch[0][1] + \strlen($headMatch[0][0]);

        if (!preg_match_all('/\s+\$([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)/u', $line, $all, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $n = \count($all[0]);
        if ($n < 1) {
            return null;
        }

        $last          = $n - 1;
        $propName      = $all[1][$last][0];
        $nameMatchOff  = $all[0][$last][1];
        $nameMatchFull = $all[0][$last][0];

        $propType = rtrim(substr($line, $typeStartByte, $nameMatchOff - $typeStartByte));
        if ('' === $propType) {
            return null;
        }

        $afterNameByte = $nameMatchOff + \strlen($nameMatchFull);
        $rest          = substr($line, $afterNameByte);
        $propComment   = '' !== trim($rest) ? trim($rest) : null;

        return [
            'head'    => $head,
            'type'    => $propType,
            'name'    => $propName,
            'comment' => $propComment,
        ];
    }
}
