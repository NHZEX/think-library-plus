<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

class ModelCollection
{
    /**
     * @var array<ModelFileItem>
     */
    private array $modelList = [];

    /**
     * @var array<string, array<string, ModelFileItem>>
     */
    private array $modelTree = [];

    public function __construct(
        private TableCollection $tableCollection,
        private string $defaultConnect,
    ) {
    }

    public function loadModelByNamespace(string $namespace): void
    {
        foreach (ModelGenerator::scanNamespace($namespace, $this->defaultConnect) as $item) {
            $this->modelList[]                                      = $item;
            $connectName                                            = $item->getConnectName() ?: $this->defaultConnect;
            $this->modelTree[$connectName][$item->getTabelName()][] = $item;
        }
    }

    public function loadModelByMapping(array $mapping): void
    {
        foreach ($mapping as $item) {
            if (!isset($item['namespace'])) {
                continue;
            }

            if (!empty($item['connect'])) {
                $this->tableCollection->loadTables($item['connect']);
            }
            $this->loadModelByNamespace($item['namespace']);
        }
    }

    public function loadModelByList(array $items): void
    {
        foreach (ModelGenerator::loadSingle($items, $this->defaultConnect) as $item) {
            $this->modelList[]                                      = $item;
            $connectName                                            = $item->getConnectName() ?: $this->defaultConnect;
            $this->modelTree[$connectName][$item->getTabelName()][] = $item;
        }
    }

    public function getModelList(): array
    {
        return $this->modelList;
    }

    /**
     * @param string $connectName
     * @return array<string, ModelFileItem>
     */
    public function getModelsByConnect(string $connectName): array
    {
        return $this->modelTree[$connectName] ?? [];
    }
}
