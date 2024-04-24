<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

use Psr\Log\LoggerInterface;
use think\helper\Arr;
use Zxin\Think\Model\ModelGenerator\Options\DefaultConfigOptions;
use Zxin\Think\Model\ModelGenerator\Options\MappingConfigOptions;

/**
 * 模型生成服务
 *
 * @template SingleItemOptions of array{table: array<string>, dir: string, namespace: string}
 */
class ModelGeneratorService
{
    private LoggerInterface      $logger;
    private DefaultConfigOptions $defaultOptions;
    private bool                 $strictTypes;
    /**
     * @var array<SingleItemOptions>
     */
    private array $single;
    /**
     * @var array<MappingConfigOptions>
     */
    private array $mapping = [];

    public function __construct(
        ?LoggerInterface $logger,
    ) {
        $this->logger = $logger;
    }

    public function execute(bool $tryRun = false): ?TableCollection
    {
        $tableCollection = new TableCollection(
            defaultOptions: $this->defaultOptions,
            mapping: $this->mapping,
            logger: $this->logger,
            tryRun: $tryRun,
        );

        $this->logger->info(sprintf("Connect: \t%s", $this->defaultOptions->getConnect()));
        $this->logger->info("Namespace: \t{$this->defaultOptions->getNamespace()}");
        $this->logger->info("BaseClass: \t{$this->defaultOptions->getBaseClass()}");

        $tableCollection->loadTables();

        $mc = $tableCollection->getModelCollection();
        $mc->loadModelByList($this->single);
        $mc->loadModelByDefaultNamespace();
        $mc->loadModelByMapping();

        $tableCollection->handleModel();

        return $tableCollection;
    }

    public function loadConfig(?array $config = null): bool
    {
        $app = app();

        $config ??= $app->config->get('model_tools', []);

        $baseClass = Arr::get($config, 'baseClass');

        if (!\is_string($baseClass) || !class_exists($baseClass)) {
            $this->logger->warning("基类不存在或者无效: {$baseClass}");
            return false;
        }

        $baseNamespace = Arr::get($config, 'baseNamespace');
        $defaultConnect = Arr::get($config, 'defaultConnect');
        $defaultConnect = $defaultConnect ?: $app->db->getConfig('default', 'mysql');

        $excludeTable = Arr::get($config, 'exclude');

        $single = Arr::get($config, 'single', []);

        $mapping = Arr::get($config, 'mapping', []);

        $this->strictTypes = Arr::get($config, 'strictTypes', true);
        $this->single = $single;
        $this->defaultOptions = DefaultConfigOptions::makeDefault(
            connect: $defaultConnect,
            namespace: $baseNamespace,
            baseClass: $baseClass,
            exclude: $excludeTable,
        );
        $this->mapping = MappingConfigOptions::fromArrSet($mapping, $this->defaultOptions);

        return true;
    }
}
