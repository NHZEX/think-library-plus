<?php

declare(strict_types=1);

namespace Zxin\Think\Annotation\Core;

use Brick\VarExporter\VarExporter;

class DumpValue
{
    private ?string $fileHash = null;
    public static bool $dumpGenerateDate = false;
    public static bool $dumpGenerateHash = false;

    public function __construct(
        private string $filename,
        private int $exportOptions = VarExporter::TRAILING_COMMA_IN_ARRAY | VarExporter::INLINE_SCALAR_LIST
    ) {
    }

    public function load(): void
    {
        if (is_file($this->filename) && is_readable($this->filename)) {
            $this->fileHash = hash_file('sha1', $this->filename, true);
        }
    }

    public function exportVar(mixed $data): string
    {
        return VarExporter::export(
            $data,
            $this->exportOptions,
        );
    }

    public function save(mixed $data): void
    {
        $dumpData = $this->exportVar($data);

        $content = "return {$dumpData};\n";
        $hash = hash('md5', $content);

        $head = <<<HEAD
            /** @noinspection ALL */
            HEAD;

        if (self::$dumpGenerateDate) {
            $date = date('c');
            $info = "// update date: {$date}\n// hash: {$hash}";
            $head = "{$info}\n\n{$head}";
        } elseif (self::$dumpGenerateHash) {
            $info = "// hash: {$hash}";
            $head = "{$info}\n\n{$head}";
        }

        $content = "<?php\n{$head}\n{$content}";

        if (false === self::$dumpGenerateDate) {
            if ($this->fileHash && hash('sha1', $content, true) === $this->fileHash) {
                return;
            }
        }

        $tempname = stream_get_meta_data($tf = tmpfile())['uri'];
        fwrite($tf, $content);
        copy($tempname, $this->filename);
    }
}
