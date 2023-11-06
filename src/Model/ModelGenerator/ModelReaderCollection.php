<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

use Zxin\Think\Model\ModelGenerator\Data\ModelFileItem;
use Zxin\Think\Model\ModelGenerator\Options\MappingConfigOptions;

class ModelReaderCollection
{
    private MappingConfigOptions $defaultOptions;

    /**
     * @var array<ModelFileItem>
     */
    private array $modelList = [];

    /**
     * @var array<string, array<string, ModelFileItem>>
     */
    private array $modelTree = [];
    private array $classSet = [];

    public function __construct(
        private TableCollection $tableCollection,
    ) {
        $this->defaultOptions = $this->tableCollection->getDefaultOptions();

        if (!$this->defaultOptions->isDefault()) {
            throw new \InvalidArgumentException('[internal] defaultOptions must be default');
        }
    }


    public function loadModelByDefaultNamespace(): void
    {
        $this->loadModelByNamespace(
            namespace: $this->defaultOptions->getNamespace(),
            connect: $this->defaultOptions->getConnect(),
        );
    }

    public function loadModelByNamespace(string $namespace, string $connect): void
    {
        foreach (ModelGeneratorHelper::scanNamespace($namespace, $connect) as $item) {
            if (isset($this->classSet[$item->getClassName()])) {
                // todo 加入重复冲突日志
                continue;
            }
            $this->classSet[$item->getClassName()] = true;
            $this->modelList[]                                      = $item;
            $connectName                                            = $item->getConnectName() ?: $connect;
            $this->modelTree[$connectName][$item->getTableName()][] = $item;
        }
    }

    public function loadModelByMapping(): void
    {
        foreach ($this->tableCollection->getMappingOptions() as $item) {
            if (!empty($item->getConnect())) {
                $this->tableCollection->loadTables($item->getConnect());
            }
            if (empty($item->getNamespace())) {
                continue;
            }
            $this->loadModelByNamespace($item->getNamespace(), $item->getConnect() ?? $this->defaultOptions->getConnect());
        }
    }

    public function loadModelByList(array $items): void
    {
        // todo 重构为配置对象
        foreach (ModelGeneratorHelper::loadSingle(items: $items, defaultConnect: $this->defaultOptions->getConnect()) as $item) {
            if (isset($this->classSet[$item->getClassName()])) {
                // todo 加入重复冲突日志
                continue;
            }
            $this->classSet[$item->getClassName()] = true;
            $this->modelList[]                                      = $item;
            $connectName                                            = $item->getConnectName() ?: $this->defaultOptions->getConnect();
            $this->modelTree[$connectName][$item->getTableName()][] = $item;
        }
    }

    public function getModelList(): array
    {
        return $this->modelList;
    }

    /**
     * @param string $connectName
     * @return array<string, array<ModelFileItem>>
     */
    public function getModelsByConnect(string $connectName): array
    {
        return $this->modelTree[$connectName] ?? [];
    }
}
