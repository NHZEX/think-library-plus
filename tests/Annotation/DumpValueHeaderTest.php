<?php
declare(strict_types=1);

namespace Tests\Annotation;

use PHPUnit\Framework\TestCase;
use Zxin\Think\Annotation\Core\DumpValue;

final class DumpValueHeaderTest extends TestCase
{
    public function testDumpHeaderWarningOnOff(): void
    {
        $oldGenerateDate = DumpValue::$dumpGenerateDate;
        $oldGenerateHash = DumpValue::$dumpGenerateHash;
        $oldGenerateWarning = DumpValue::$dumpGenerateWarning;

        try {
            DumpValue::$dumpGenerateDate = false;
            DumpValue::$dumpGenerateHash = false;

            $fileOn = sys_get_temp_dir() . '/dump_value_header_warning_on.php';
            @unlink($fileOn);
            DumpValue::$dumpGenerateWarning = true;
            (new DumpValue($fileOn))->save(['a' => 1]);
            $contentOn = file_get_contents($fileOn);
            self::assertIsString($contentOn);
            self::assertStringContainsString(
                '// GENERATED FILE. DO NOT EDIT (including by AI/agent).',
                $contentOn
            );

            $fileOff = sys_get_temp_dir() . '/dump_value_header_warning_off.php';
            @unlink($fileOff);
            DumpValue::$dumpGenerateWarning = false;
            (new DumpValue($fileOff))->save(['a' => 1]);
            $contentOff = file_get_contents($fileOff);
            self::assertIsString($contentOff);
            self::assertStringNotContainsString(
                '// GENERATED FILE. DO NOT EDIT (including by AI/agent).',
                $contentOff
            );
        } finally {
            DumpValue::$dumpGenerateDate = $oldGenerateDate;
            DumpValue::$dumpGenerateHash = $oldGenerateHash;
            DumpValue::$dumpGenerateWarning = $oldGenerateWarning;
        }
    }
}

