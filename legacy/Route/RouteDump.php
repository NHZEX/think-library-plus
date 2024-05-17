<?php

declare(strict_types=1);

namespace Zxin\Think\Route;

use Brick\VarExporter\VarExporter;
use think\App;
use Zxin\Think\Annotation\Core\DumpValue;

class RouteDump extends DumpValue
{
    public static function dump(): void
    {
        echo '====== RouteDump ======' . PHP_EOL;
        $path = RouteLoader::getDumpFilePath();
        $dump = new self(
            $path,
            VarExporter::TRAILING_COMMA_IN_ARRAY | VarExporter::INLINE_SCALAR_LIST
        );
        $dump->scanAnnotation();
        echo '========== DONE ==========' . PHP_EOL;
    }

    public function saveData(array $items)
    {
        $this->load();
        $this->save($items);
    }

    public function scanAnnotation(): void
    {
        $rs    = new RouteScanning(App::getInstance());
        $items = $rs->scan();

        $this->load();
        $this->save($items);
    }
}
