<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

use Composer\Pcre\Preg;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use think\db\ConnectionInterface;
use think\db\PDOConnection;
use think\helper\Str;
use Zxin\Think\Model\ModelGenerator\Data\ModelFileItem;
use Zxin\Think\Model\ModelGenerator\Data\RecordRow;
use Zxin\Think\Model\ModelGenerator\Options\DefaultConfigOptions;
use Zxin\Think\Model\ModelGenerator\Options\MappingConfigOptions;

class TableCollection
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $tableTree = [];

    private ModelReaderCollection $modelCollection;

    /**
     * @var array<RecordRow>
     */
    private array $recordRows = [];

    private array $efficientModelSet = [];

    public function __construct(
        private DefaultConfigOptions $defaultOptions,
        /** @var array<MappingConfigOptions> */
        private array            $mapping,
        private ?LoggerInterface $logger = null,
        private bool             $tryRun = false,
    ) {
        $this->logger          ??= new NullLogger();
        $this->modelCollection = new ModelReaderCollection($this);
    }

    public function getDefaultOptions(): MappingConfigOptions
    {
        return $this->defaultOptions;
    }

    /**
     * @return MappingConfigOptions[]
     */
    public function getMappingOptions(): array
    {
        return $this->mapping;
    }

    public static function resolveDbConnect(string $name): ConnectionInterface|PDOConnection
    {
        return app()->db->connect($name);
    }

    public function loadTables(?string $connectName = null): array
    {
        $connectName ??= $this->defaultOptions->getConnect();

        $connection = self::resolveDbConnect($connectName);

        return $this->tableTree[$connectName] ??= ModelGeneratorHelper::queryTables($connection);
    }

    public function getModelCollection(): ModelReaderCollection
    {
        return $this->modelCollection;
    }

    public function handleModel(): void
    {
        $this->recordRows        = [];
        $this->efficientModelSet = [];

        foreach ($this->tableTree as $connectName => $tables) {
            $defaultExclude = $this->defaultOptions->getExclude() ?? [];
            $tables = array_filter($tables, function (string $table) use ($connectName, $defaultExclude) {
                if (\in_array($table, $defaultExclude)) {
                    return false;
                }
                return null === $this->resolveMappingConfigOptionsByExclude($table, $connectName);
            }, ARRAY_FILTER_USE_KEY);
            $this->handleModelByConnect($connectName, $tables);

            // todo 处理当前连接的单个模型声明
        }

        foreach ($this->modelCollection->getModelList() as $item) {
            if (!isset($this->efficientModelSet[$item->getObjId()])) {
                $this->recordRows[] = new RecordRow(
                    connect: $item->getConnectName(),
                    table: $item->getTableName(),
                    className: $item->getClassname(),
                    filename: $item->getPathname(),
                    status: 'LOSS'
                );
            }
        }
    }

    /**
     * @return array<RecordRow>
     */
    public function getRecordRows(): array
    {
        return $this->recordRows;
    }

    private function handleModelByConnect(string $connectName, array $tables): void
    {
        $modelList = $this->modelCollection->getModelsByConnect($connectName);

        foreach ($tables as $table => $comment) {
            if (isset($modelList[$table])) {
                foreach ($modelList[$table] as $item) {
                    if ($item->hasFile()) {
                        $this->updateModel($connectName, $table, $item);
                        $this->efficientModelSet[$item->getObjId()] = true;
                    } else {
                        $this->createModelByItem($item, $connectName, $table, $comment);
                        $this->efficientModelSet[$item->getObjId()] = true;
                    }
                }
            } else {
                $record = $this->createModel($connectName, $table, $comment);
                if (null !== $record) {
                    $this->recordRows[] = $record;
                }
            }
        }
    }

    public function createModel(string $connectName, string $table, string $comment): ?RecordRow
    {
        $matchOption = $this->resolveMappingConfigOptions($table, $connectName);

        $content = $this->generateModel($connectName, $table, $comment, $matchOption, $className);

        if (empty($content)) {
            $this->logger->warning("table invalid, table: [{$connectName}]{$table}");
            return null;
        }

        $savePath = ModelGeneratorHelper::classToPath($className);

        if (empty($savePath)) {
            $this->logger->warning("Class name invalid, table: [{$connectName}]{$table}, class: {$className}");
            return null;
        }

        $record = new RecordRow(
            connect: $connectName,
            table: $table,
            className: $className,
            filename: $savePath,
            status: 'CREATE',
            content: $content,
            change: true,
        );

        if (!$this->tryRun) {
            self::writeFile($savePath, $content);
        }

        return $record;
    }

    private function createModelByItem(ModelFileItem $model, string $connectName, string $table, string $comment): bool
    {
        $_className = $model->getClassname();
        $content = $this->_generateModel(
            connectName: $connectName,
            table: $table,
            comment: $comment,
            namespace: $model->getNamespace(),
            baseClass: $this->defaultOptions->getBaseClass(),
            className: $_className,
        );

        if (empty($content)) {
            $this->logger->warning("table invalid, table: [{$connectName}]{$table}");
            return false;
        }

        $this->recordRows[] = new RecordRow(
            connect: $connectName,
            table: $table,
            className: $model->getClassname(),
            filename: $model->getPathname(),
            status: 'CREATE',
            content: $content,
            change: true,
        );

        if (!$this->tryRun) {
            self::writeFile($model->getPathname(), $content);
        }

        return true;
    }

    private function updateModel(string $connectName, string $table, ModelFileItem $model): void
    {
        $content = $this->_updateModel($connectName, $table, $model);

        $isChange = file_get_contents($model->getPathname()) !== $content;

        $status = match (true) {
            empty($content) => 'FAIL',
            $isChange => 'UPDATE',
            default => 'OK',
        };

        $this->recordRows[] = new RecordRow(
            connect: $connectName,
            table: $table,
            className: $model->getClassname(),
            filename: $model->getPathname(),
            status: $status,
            content: $content,
            change: $isChange,
        );

        if (!$this->tryRun && $isChange) {
            $model->writeFileContent($content);
        }
    }

    private function generateModel(
        string  $connectName,
        string  $table,
        string  $comment,
        ?MappingConfigOptions $matchOption,
        ?string &$className,
    ): ?string {

        $namespace = $matchOption?->getNamespace() ?? $this->defaultOptions->getNamespace();
        $baseClass = $matchOption?->getBaseClass() ?? $this->defaultOptions->getBaseClass();

        return $this->_generateModel(
            connectName: $connectName,
            table: $table,
            comment: $comment,
            namespace: $namespace,
            baseClass: $baseClass,
            className: $className,
        );
    }

    private function _generateModel(
        string  $connectName,
        string  $table,
        string  $comment,
        string  $namespace,
        string  $baseClass,
        ?string &$className = null,
    ): ?string {
        $namespace  = ltrim($namespace, '\\');
        $connection = self::resolveDbConnect($connectName);
        $fields     = ModelGeneratorHelper::queryTableFields($connection, $table);

        if ($fields->isEmpty()) {
            return null;
        }

        $phpFile = new PhpFile();

        $phpFile->setStrictTypes();

        $phpNamespace = $phpFile->addNamespace($namespace);
        $phpNamespace->addUse($baseClass);

        if (null !== $className) {
            $_name = substr($className, strrpos($className, '\\') + 1);
        } else {
            $_name = Str::studly($table) . 'Model';
        }

        $phpClass = $phpNamespace
            ->addClass($_name)
            ->setFinal()
            ->setExtends($baseClass);

        $className = $phpNamespace->getName() . '\\' . $phpClass->getName();

        // 注释头
        if ($comment) {
            $phpClass->addComment("Model: {$comment}.\n");
        } else {
            $phpClass->addComment("Model: Table of {$table}.\n");
        }
        // 注释属性
        $propertyCollection = PropertyCollection::fromFields($fields);
        $phpClass->addComment($propertyCollection->outputAllText());

        // 类元声明
        // > 连接
        if ($connectName !== $this->defaultOptions->getConnect()) {
            $phpClass->addProperty('connection', $connectName);
        }
        // > 表名
        $phpClass->addProperty('table', $table);
        // > 主键
        $priFields = $fields->where('COLUMN_KEY', '=', 'PRI')->column('COLUMN_NAME');
        if ($priFields) {
            if (\count($priFields) === 1) {
                $pkValue = $priFields[0];
            } else {
                $pkValue = $priFields;
            }

            $phpClass->addProperty('pk', $pkValue);
        }

        return self::printPhpFile($phpFile);
    }

    private function _updateModel(string $connectName, string $table, ModelFileItem $model): ?string
    {
        $connection = self::resolveDbConnect($connectName);

        // 加载类文件
        $phpFile  = PhpFile::fromCode($model->getFileContent());
        $phpClass = $phpFile->getClasses()[$model->getClassname()];

        $rawComment = $phpClass->getComment();

        $liens = [];
        foreach (explode("\n", $rawComment) as $i => $line) {
            if (!preg_match('/^\s*@property/', $line)) {
                $liens[] = [$i, $line, 'raw'];
                continue;
            }

            $line = trim($line);

            if (!preg_match('/^@(property\S*?)\s+(\S+)\s+\$(\S+)(?:\s([\S\s]+))?/', $line, $matchs, PREG_UNMATCHED_AS_NULL)) {
                $liens[] = [$i, $line, 'raw'];
                continue;
            }

            $head        = trim($matchs[1]);
            $propType    = trim($matchs[2]);
            $propName    = trim($matchs[3]);
            $propComment = $matchs[4] ? trim($matchs[4]) : null;

            $line = [$i, $line, 'property', [
                'head'    => $head,
                'type'    => $propType,
                'name'    => $propName,
                'comment' => $propComment,
            ]];

            $liens[] = $line;
        }


        // 加载表字段
        $fields             = ModelGeneratorHelper::queryTableFields($connection, $table);
        $propertyCollection = PropertyCollection::fromFields($fields);

        // todo 支持更新主键

        // 重构字段内容
        $headLines  = [];
        $headFinish = false;
        $endLines   = [];
        foreach ($liens as $line) {
            $kind     = $line[2];
            $opts     = $line[3] ?? null;
            $propName = $opts['name'] ?? null;
            $isField  = $propName && $propertyCollection->hasProperty($propName);

            if ($isField) {
                $propertyCollection->appendRef($propName, $opts['type'], $opts['comment']);
            }

            if (!$headFinish && ('raw' === $kind || !$isField)) {
                $headLines[] = $line[1];
            } elseif (!$headFinish && $isField) {
                $headFinish = true;
            } elseif (!$isField) {
                $endLines[] = $line[1];
            }
        }

        $propLines = [];
        foreach ($propertyCollection->outPropertyLines() as $line) {
            $propLines[] = $line;
        }

        if ($headLines && $headLines[array_key_last($headLines)] !== '') {
            $headLines[] = '';
        }
        if ($endLines && $endLines[0] !== '') {
            array_unshift($endLines, '');
        }

        $lines = [];
        foreach ([...$headLines, ...$propLines, ...$endLines] as $str) {
            if ($str) {
                $lines[] = ' * ' . $str;
            } else {
                $lines[] = ' *';
            }
        }
        $comment = join("\n", $lines);

        $fileContent = $model->getFileContent();

        // 建立注解替换匹配
        $pattern = preg_quote(join("\n", array_map(fn ($str) => $str, explode("\n", $rawComment))), '#');
        $pattern = Preg::replace('#^|\n#', "$0\\s*?\*\\s*?", $pattern);
        $pattern = "\/\*\*\n{$pattern}\\s*?\*\/";
        $comment = "/**\n{$comment}\n */";

        $output = Preg::replace("#{$pattern}#m", $comment, $fileContent, 1, $count);

        if (0 === $count) {
            $this->logger->warning("更新模型注释可能失败: {$model->getClassname()}");

            $output = null;
        }

        return $output;
    }

    public static function printPhpFile(PhpFile $phpFile): string
    {
        $printer              = new Printer();
        $printer->indentation = '    ';
        return $printer->printFile($phpFile);
    }

    private static function writeFile(string $filename, string $content): void
    {
        $dirname = \dirname($filename);

        if (!file_exists($dirname)) {
            mkdir($dirname, 0755, true);
        }

        $flags = LOCK_EX;
        if (\defined('TEST_MODEL_GENERATOR_USE_VFS') && TEST_MODEL_GENERATOR_USE_VFS && str_starts_with($filename, 'vfs://')) {
            $flags &= ~LOCK_EX;
        }
        file_put_contents($filename, $content, $flags);
    }

    private function resolveMappingConfigOptions(string $table, ?string $connectName): ?MappingConfigOptions
    {
        foreach ($this->mapping as $item) {

            if ($item->testMatchOption($table, $connectName)) {
                return $item;
            }
        }

        return null;
    }

    private function resolveMappingConfigOptionsByExclude(string $table, string $connectName): ?MappingConfigOptions
    {
        foreach ($this->mapping as $item) {
            if ($item->testExcludeOption($table, $connectName)) {
                return $item;
            }
        }

        return null;
    }
}
