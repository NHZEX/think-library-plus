<?php

namespace Zxin\Think\Annotation\Core;

use SplFileObject;
use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Symfony\Component\VarExporter\VarExporter;

class DumpValue
{
    private ?string $filehash = null;

    public function __construct(private string $filename)
    {
    }

    public function load(): void
    {
        if (is_file($this->filename) && is_readable($this->filename)) {
            $sf = new SplFileObject($this->filename, 'r');
            $sf->seek(2);
            [, $lastHash] = explode(':', $sf->current() ?: ':');
            $lastHash = trim($lastHash);
            $content = $sf->fread($sf->getSize() - $sf->ftell());
            if ($lastHash === hash('md5', $content)) {
                $this->filehash = $lastHash;
            }
        }
    }

    /**
     * @param mixed $data
     */
    public function exportVar($data, string $default = '[]'): string
    {
        try {
            $dumpData = VarExporter::export($data);
        } catch (ExceptionInterface) {
            $dumpData = $default;
        }

        return $dumpData;
    }

    /**
     * @param mixed $data
     */
    public function save($data, string $default = '[]'): void
    {
        $dumpData = $this->exportVar($data, $default);

        $contents = "return {$dumpData};\n";
        $hash = hash('md5', $contents);

        if ($this->filehash === $hash) {
            return;
        }

        $date = date('c');
        $info = "// update date: {$date}\n// hash: {$hash}";

        $tempname = stream_get_meta_data($tf = tmpfile())['uri'];
        fwrite($tf, "<?php\n{$info}\n{$contents}");
        copy($tempname, $this->filename);
    }
}
