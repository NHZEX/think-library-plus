<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\console\Table;
use think\helper\Arr;
use Zxin\Think\Model\ModelGenerator\Options\DefaultConfigOptions;
use Zxin\Think\Model\ModelGenerator\Options\MappingConfigOptions;

/**
 * 批量创建数据结构到模型
 *
 * @template SingleItemOptions of array{table: array<string>, dir: string, namespace: string}
 */
class CreateModel extends Command
{
    public const FILTE_TABLE = ['_phinxlog', 'activity_log'];

    public const OUTPUT_ALIGN = 22;
    private LoggerInterface      $logger;
    private DefaultConfigOptions $defaultOptions;
    private bool                 $strictTypes;
    private array $excludeTable;
    /**
     * @var array<SingleItemOptions>
     */
    private array $single;
    /**
     * @var array<MappingConfigOptions>
     */
    private array $mapping = [];

    public function __construct()
    {
        parent::__construct();
        $this->logger = new NullLogger();
    }

    public function configure()
    {
        $this->setName('mc')
            ->addOption('connect', 'c', Option::VALUE_OPTIONAL, '指定连接', '')
            ->addOption('dir', 'd', Option::VALUE_OPTIONAL, '模型目录', './app/Model')
            ->addOption('namespace', 'a', Option::VALUE_OPTIONAL, '命名空间', 'app\\Model')
            ->addOption('print', 'p', Option::VALUE_NONE, '打印')
            ->addOption('save', 's', Option::VALUE_NONE, '保存（只能和指定表同时使用, 且与打印互斥）')
            ->addArgument('table', Argument::OPTIONAL, '指定表');
    }

    /**
     * @param Input  $input
     * @param Output $output
     * @return int
     */
    public function execute(Input $input, Output $output): int
    {
        if (!$this->loadConfig()) {
            return 1;
        }

        $tableCollection = new TableCollection(
            defaultOptions: $this->defaultOptions,
            mapping: $this->mapping,
            excludeTable: $this->excludeTable,
            logger: $this->logger,
        );

        $output->info(sprintf("Connect: \t%s", $this->defaultOptions->getConnect()));
        $output->info("Namespace: \t{$this->defaultOptions->getNamespace()}");
        $output->info("BaseClass: \t{$this->defaultOptions->getBaseClass()}");

        $tableCollection->loadTables();

        $mc = $tableCollection->getModelCollection();
        $mc->loadModelByDefaultNamespace();
        $mc->loadModelByMapping();
        $mc->loadModelByList($this->single);


        $tableCollection->handleModel();

        $table = new Table();
        $table->setHeader(['connect', 'table', 'model', 'status']);

        foreach ($tableCollection->getRecordRows() as $row) {
            $table->addRow($row);
        }

        $this->output->write($table->render());

        return 0;
    }

    private function loadConfig(): bool
    {
        $config = $this->app->config->get('model_tools', []);

        $baseClass = Arr::get($config, 'baseClass');

        if (!\is_string($baseClass) || !class_exists($baseClass)) {
            $this->output->warning("基类不存在或者无效: {$baseClass}");
            return false;
        }

        $baseNamespace = Arr::get($config, 'baseNamespace');
        $defaultConnect = Arr::get($config, 'defaultConnect');
        $defaultConnect = $defaultConnect ?: $this->app->db->getConfig('default', 'mysql');

        $excludeTable = Arr::get($config, 'exclude');

        $single = Arr::get($config, 'single', []);

        $mapping = Arr::get($config, 'mapping', []);

        $this->strictTypes = Arr::get($config, 'strictTypes', true);
        $this->excludeTable = $excludeTable;
        $this->single = $single;
        $this->defaultOptions = DefaultConfigOptions::makeDefault(
            connect: $defaultConnect,
            namespace: $baseNamespace,
            baseClass: $baseClass,
        );
        $this->mapping = MappingConfigOptions::fromArrSet($mapping);

        return true;
    }
}
