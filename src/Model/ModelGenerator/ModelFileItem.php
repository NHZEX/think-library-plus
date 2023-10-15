<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

use think\Model;

class ModelFileItem
{
    private int    $objId;
    private ?string $connectName = null;

    private string $pathname;

    /**
     * @var class-string<Model>
     */
    private string $classname;

    public function __construct(
        private string $namespace,
        private string $filename,
        private string $dir,
        private ?string $tableName = null,
        private ?string $defaultConnect = null,
        /**
         * @var \ReflectionClass<Model>|null
         */
        private ?\ReflectionClass $reflectionClass = null,
    ) {
        $this->objId = spl_object_id($this);

        $this->pathname  = $dir . DIRECTORY_SEPARATOR . $filename;
        $this->classname = $this->namespace . '\\' . substr($this->filename, 0, -4);
    }

    public function getObjId(): int
    {
        return $this->objId;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getClassname(): string
    {
        return $this->classname;
    }

    public function getPathname(): string
    {
        return $this->pathname;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * @return \ReflectionClass<Model>|null
     */
    public function getReflectionClass(): \ReflectionClass
    {
        // todo 实现加载异常处理

        return $this->reflectionClass ??= new \ReflectionClass($this->classname);
    }

    public function isValidModel(): bool
    {
        return !$this->getReflectionClass()->isAbstract();
    }

    public function makeInstance(): Model
    {
        // todo 实现加载异常处理
        // \call_user_func([$this->classname, 'connect'], 'main')

        return new $this->classname();
    }

    private \ReflectionObject $reflectionObject;
    private Model $internalObject;

    public function makeReflectionInstance(): \ReflectionObject
    {
        if (!empty($this->reflectionObject)) {
            return $this->reflectionObject;
        }

        $this->internalObject = $this->getReflectionClass()->newInstanceWithoutConstructor();
        return $this->reflectionObject ??= new \ReflectionObject($this->internalObject);
    }

    public function getTabelName(): ?string
    {
        if (null !== $this->tableName) {
            return $this->tableName;
        }

        $prop = $this->makeReflectionInstance()->getProperty('table');
        $prop->setAccessible(true);
        $this->tableName = $prop->getValue($this->internalObject);

        if (empty($this->tableName)) {
            $prop = $this->makeReflectionInstance()->getProperty('name');
            $prop->setAccessible(true);
            $_name = $prop->getValue($this->internalObject);

            if ($_name) {
                $opts = $this->getConnectOptions();
                if ($opts) {
                    $this->tableName = ($opts['prefix'] ?? '') . $_name;
                }
            }
        }

        return $this->tableName;
    }

    public function getConnectOptions(): ?array
    {
        $name = $this->getConnectName();

        if (empty($name)) {
            return null;
        }

        return app()->db->getConfig("connections.{$name}");

    }

    public function getConnectName(): ?string
    {
        if (null !== $this->connectName) {
            return $this->connectName;
        }

        $prop = $this->makeReflectionInstance()->getProperty('connection');
        $prop->setAccessible(true);

        return $this->connectName ??= ($prop->getValue($this->internalObject) ?: $this->defaultConnect);
    }

    public function getFileContent(): string
    {
        return file_get_contents($this->pathname);
    }

    public function writeFileContent(string $content): bool
    {
        return file_put_contents($this->pathname, $content, LOCK_EX) > 0;
    }
}
