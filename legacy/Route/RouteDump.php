<?php

declare(strict_types=1);

namespace Zxin\Think\Route;

use Brick\VarExporter\VarExporter;
use think\App;
use Zxin\Think\Annotation\Core\DumpCommand;
use Zxin\Think\Annotation\Core\DumpValue;

class RouteDump extends DumpValue
{
    public static function dump(?DumpCommand $command = null): void
    {
        $command ??= DumpCommand::default();
        if ($command->shouldShowHelp()) {
            echo $command->help('dump-route', 'Dump route annotation storage.');
            return;
        }

        $path = RouteLoader::getDumpFilePath();
        $dump = new self(
            $path,
            VarExporter::TRAILING_COMMA_IN_ARRAY | VarExporter::INLINE_SCALAR_LIST
        );
        $before = is_file($path) ? hash_file('sha1', $path) : null;
        $startedAt = microtime(true);
        $report = $dump->scanAnnotationWithReport();
        $elapsedMs = (microtime(true) - $startedAt) * 1000;
        clearstatcache(true, $path);
        $after = is_file($path) ? hash_file('sha1', $path) : null;

        echo $command->render(
            'dump-route',
            'RouteDump',
            'ok',
            $before !== $after,
            $path,
            $elapsedMs,
            $report['summary'],
            $report['details']
        );
    }

    public function saveData(array $items)
    {
        $this->load();
        $this->save($items);
    }

    public function scanAnnotation(): void
    {
        $this->scanAnnotationWithReport();
    }

    /**
     * @return array{data: array, summary: array<string, int>, details: list<array<string, scalar|null>>}
     */
    public function scanAnnotationWithReport(): array
    {
        $rs    = new RouteScanning(App::getInstance());
        $report = $rs->scanWithReport();

        $this->load();
        $this->save($report['data']);

        return $report;
    }
}
