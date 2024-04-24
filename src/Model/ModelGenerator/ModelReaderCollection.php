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
            $this->defaultOptions,
        );
    }

    public function loadModelByNamespace(MappingConfigOptions $options): void
    {
        foreach (ModelGeneratorHelper::scanNamespace($options->getNamespace(), $options->getConnect()) as $pathname) {

            $item = ModelFileItem::fromFile(
                namespace: $options->getNamespace(),
                filename: basename($pathname),
                dir: \dirname($pathname),
                options: $options,
            );
            if (null === $item) {
                // todo 做日志记录
                continue;
            }
            if (isset($this->classSet[$item->getClassName()])) {
                // todo 加入重复冲突日志
                continue;
            }
            $this->classSet[$item->getClassName()] = true;
            $this->modelList[]                                      = $item;
            $connectName                                            = $item->getConnectName();
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
            $this->loadModelByNamespace($item);
        }
    }

    public function loadModelByList(): void
    {
        foreach (ModelGeneratorHelper::loadSingle(items: $this->tableCollection->getSingleOptions()) as $item) {
            if (isset($this->classSet[$item->getClassName()])) {
                // todo 加入重复冲突日志
                continue;
            }
            $this->classSet[$item->getClassName()] = true;
            $this->modelList[]                                      = $item;
            $connectName                                            = $item->getConnectName();
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
