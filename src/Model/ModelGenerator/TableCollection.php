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
use Zxin\Think\Model\ModelGenerator\Options\DefaultConfigOptions;
use Zxin\Think\Model\ModelGenerator\Options\MappingConfigOptions;

class TableCollection
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $tableTree = [];

    private ModelCollection $modelCollection;

    private array $recordRows = [];

    private array $efficientModelSet = [];

    public function __construct(
        private DefaultConfigOptions $defaultOptions,
        /** @var array<MappingConfigOptions> */
        private array            $mapping,
        private array            $excludeTable,
        private ?LoggerInterface $logger = null,
    ) {
        $this->logger          ??= new NullLogger();
        $this->modelCollection = new ModelCollection($this);
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

        return $this->tableTree[$connectName] ??= ModelGenerator::queryTables($connection);
    }

    public function getModelCollection(): ModelCollection
    {
        return $this->modelCollection;
    }

    public function getTableTree(): array
    {
        return $this->tableTree;
    }

    public function getTables(?string $connectName = null): ?array
    {
        $connectName ??= $this->defaultOptions->getConnect();

        return $this->tableTree[$connectName] ?? null;
    }

    public function handleModel(): void
    {
        $this->recordRows        = [];
        $this->efficientModelSet = [];

        foreach ($this->tableTree as $connectName => $tables) {
            $this->handleModelByConnect($connectName, $tables);
        }

        foreach ($this->modelCollection->getModelList() as $item) {
            if (!isset($this->efficientModelSet[$item->getObjId()])) {
                $this->recordRows[] = [$item->getConnectName(), $item->getTabelName(), $item->getClassname(), 'LOSS'];
            }
        }
    }

    public function getRecordRows(): array
    {
        return $this->recordRows;
    }

    private function handleModelByConnect(string $connectName, array $tables): void
    {
        $modelList = $this->modelCollection->getModelsByConnect($connectName);

        foreach ($tables as $table => $comment) {
            if (\in_array($table, $this->excludeTable)) {
                // 忽略排除项目
                continue;
            }

            if (isset($modelList[$table])) {
                foreach ($modelList[$table] as $item) {
                    $this->recordRows[] = [$connectName, $table, $item->getClassname(), 'UPDATE'];
                    $this->updateModel($connectName, $table, $item);
                    $this->efficientModelSet[$item->getObjId()] = true;
                }
            } else {
                $this->createModel($connectName, $table, $comment);
            }
        }
    }

    private function createModel(string $connectName, string $table, string $comment): void
    {
        $matchOption = $this->resolveTableNamespace($table, $connectName);

        $content = $this->generateModel($connectName, $table, $comment, $matchOption, $className);

        $savePath = ModelGenerator::classToPath($className);

        $this->recordRows[] = [$connectName, $table, $className, 'CREATE'];

        self::writeFile($savePath, $content);
    }

    private function updateModel(string $connectName, string $table, ModelFileItem $model): void
    {
        $content = $this->_updateModel($connectName, $table, $model);

        if ($content) {
            $model->writeFileContent($content);
        }
    }

    private function generateModel(
        string  $connectName,
        string  $table,
        string  $comment,
        ?MappingConfigOptions $matchOption,
        ?string &$className,
    ): string {
        $connection = self::resolveDbConnect($connectName);
        $fields     = ModelGenerator::queryTableFields($connection, $table);

        $phpFile = new PhpFile();

        $phpFile->setStrictTypes();

        $namespace = $matchOption?->getNamespace() ?? $this->defaultOptions->getNamespace();
        $baseClass = $matchOption?->getBaseClass() ?? $this->defaultOptions->getBaseClass();

        $phpNamespace = $phpFile->addNamespace($namespace);
        $phpNamespace->addUse($baseClass);

        $phpClass = $phpNamespace
            ->addClass(Str::studly($table) . 'Model')
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
        $fields             = ModelGenerator::queryTableFields($connection, $table);
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
        $lines = [
            ...$headLines,
            ...$propLines,
            ...$endLines,
        ];

        $comment = ' * ' . join("\n * ", $lines);

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

        file_put_contents($filename, $content, LOCK_EX);
    }

    private function resolveTableNamespace(string $table, ?string $connectName): ?MappingConfigOptions
    {
        foreach ($this->mapping as $item) {

            if ($item->testMatchOption($table, $connectName)) {
                return $item;
            }
        }

        return null;
    }
}
