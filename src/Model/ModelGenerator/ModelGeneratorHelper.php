<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

use Composer\Autoload\ClassLoader;
use think\Collection;
use think\db\ConnectionInterface;
use Zxin\Think\Model\ModelGenerator\Data\ModelFileItem;
use Zxin\Think\Model\ModelGenerator\Options\SingleItemOptions;

class ModelGeneratorHelper
{
    public static function queryTables(ConnectionInterface $connection): array
    {
        $tables = $connection
            ->table('information_schema.tables')
            // 默认不处理视图 VIEW
            ->where('TABLE_TYPE', '=', 'BASE TABLE')
            ->whereRaw('`TABLE_SCHEMA`=SCHEMA()')
            ->select();

        return $tables->column('TABLE_COMMENT', 'TABLE_NAME');
    }

    public static function queryTableFields(ConnectionInterface $connection, string $table): Collection
    {
        return $connection
            ->table('information_schema.COLUMNS')
            ->whereRaw('`TABLE_SCHEMA`=SCHEMA()')
            ->where('table_name', '=', $table)
            ->order('ORDINAL_POSITION')
            ->select();
    }

    public static function findNamespacePaths(string $class, ?ClassLoader $loader, ?array &$baseDirs = null): ?array
    {
        $class = ltrim($class, '\\');
        if (null === $loader) {
            $loaders = ClassLoader::getRegisteredLoaders();
            $loader  = current($loaders);
        }

        $logicalPathPsr4 = $loader->getPrefixesPsr4();

        $subPath = $class;
        $lastPos = null;

        $dirs = null;

        $notFound = [];
        $maxLoop = 128;

        do {
            if ($maxLoop-- < 0) {
                throw new \LogicException('Max loop reached');
            }
            $tmp = null;
            if ($lastPos) {
                $tmp = substr($subPath, $lastPos + 1);

            }
            $subPath = $lastPos ? substr($subPath, 0, $lastPos) : $subPath;
            $search  = $subPath . '\\';

            if ($tmp) {
                array_unshift($notFound, $tmp);
            }

            if (isset($logicalPathPsr4[$search])) {

                $dirs = $logicalPathPsr4[$search];

                break;
            }

        } while (false !== $lastPos = strrpos($subPath, '\\'));

        if (empty($dirs)) {
            return null;
        }

        $baseDirs = $dirs;

        return array_map(fn ($dir) => join(DIRECTORY_SEPARATOR, [$dir, ...$notFound]), $dirs);
    }

    public static function scanNamespace(string $namespace, ?string $defaultConnect = null): \Generator
    {
        $dirs = self::findNamespacePaths($namespace, null);

        if (empty($dirs)) {
            return;
        }

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (scandir($dir) as $filename) {
                if (!str_ends_with($filename, '.php')) {
                    continue;
                }
                if ('Base.php' === $filename) {
                    continue;
                }

                yield $dir . DIRECTORY_SEPARATOR . $filename;
            }
        }
    }

    /**
     * @param array<SingleItemOptions> $items
     */
    public static function loadSingle(array $items): \Generator
    {
        foreach ($items as $item) {
            $class = $item->getClass();
            $table = $item->getTable();
            $connect = $item->getConnect();

            if (!class_exists($class)) {
                yield ModelFileItem::fromNewClass(
                    classname: $class,
                    connect: $connect,
                    tableName: $table,
                    options: $item,
                );
                continue;
            }

            $ref       = new \ReflectionClass($class);
            $namespace = $ref->getNamespaceName();
            $filename  = $ref->getFileName();

            $item = ModelFileItem::fromReflection(
                namespace: $namespace,
                filename: basename($filename),
                dir: \dirname($filename),
                defaultConnect: $connect,
                tableName: $table,
                reflection: $ref,
                options: $item,
            );
            if (null === $item) {
                // todo 做日志记录
                continue;
            }
            yield $item;
        }
    }

    public static function classToPath(string $class): ?string
    {
        $pathname = ModelGeneratorHelper::findNamespacePaths($class, null);

        if (empty($pathname)) {
            return null;
        }

        return $pathname[0] . '.php';
    }
}
