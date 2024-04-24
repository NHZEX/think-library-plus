<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator\Data;

use think\Model;
use Zxin\Think\Model\ModelGenerator\ModelGeneratorHelper;
use Zxin\Think\Model\ModelGenerator\Options\ItemOptionsInterface;
use Zxin\Think\Model\ModelGenerator\Options\MappingConfigOptions;
use Zxin\Think\Model\ModelGenerator\Options\SingleItemOptions;

class ModelFileItem
{
    private int    $objId;
    private ?string $connectName = null;
    private bool $fileExists = true;

    private function __construct(
        private string $namespace,
        private string $filename,
        private string $dir,
        private string $pathname,
        /**
         * @var class-string<Model>
         */
        private string $classname,
        /**
         * @var \ReflectionClass<Model>|null
         */
        private ?\ReflectionClass $reflectionClass,
        /**
         * @var ItemOptionsInterface|MappingConfigOptions|SingleItemOptions
         */
        private ItemOptionsInterface $options,
        private ?string $tableName = null,
    ) {
        $this->objId = spl_object_id($this);
    }

    public static function fromFile(
        string $namespace,
        string $filename,
        string $dir,
        ItemOptionsInterface $options,
    ): ?self {
        return self::fromReflection(
            namespace: $namespace,
            filename: $filename,
            dir: $dir,
            tableName: null,
            reflection: null,
            options: $options,
        );
    }

    public static function fromReflection(
        string $namespace,
        string $filename,
        string $dir,
        ?string $tableName,
        ?\ReflectionClass $reflection,
        ItemOptionsInterface $options,
    ): ?self {
        $pathname  = $dir . DIRECTORY_SEPARATOR . $filename;
        $classname = $namespace . '\\' . substr($filename, 0, -4);

        if (null === $reflection) {
            $filePath = ModelGeneratorHelper::classToPath($classname);
            if (!file_exists($filePath)) {
                return null;
            }
            if (class_exists($classname)) {
                $reflection = new \ReflectionClass($classname);
            } else {
                return null;
            }
        }

        if ($reflection->isAbstract() || $reflection->isTrait() || $reflection->isInterface()) {
            return null;
        };

        return new self(
            namespace: $namespace,
            filename: $filename,
            dir: $dir,
            pathname: $pathname,
            classname: $classname,
            reflectionClass: $reflection,
            options: $options,
            tableName: $tableName,
        );
    }

    public static function fromNewClass(
        string $classname,
        string $connect,
        ?string $tableName,
        SingleItemOptions $options,
    ) {
        $classname = ltrim($classname, '\\');

        $namespace = substr($classname, 0, strrpos($classname, '\\'));
        $pathname = ModelGeneratorHelper::classToPath($classname);

        $filename = basename($pathname);
        $dir = \dirname($pathname);

        $obj = new self(
            namespace: $namespace,
            filename: $filename,
            dir: $dir,
            pathname: $pathname,
            classname: $classname,
            reflectionClass: null,
            options: $options,
            tableName: $tableName,
        );

        $obj->connectName = $connect;
        $obj->fileExists = false;

        return $obj;
    }

    public function getObjId(): int
    {
        return $this->objId;
    }

    public function getOptions(): ItemOptionsInterface
    {
        return $this->options;
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

    public function hasFile(): bool
    {
        return $this->fileExists || file_exists($this->getPathname());
    }

    public function getDir(): string
    {
        return $this->dir;
    }

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

        $model = $this->getReflectionClass()->newInstanceWithoutConstructor();

        if (!$model instanceof Model) {
            throw new \RuntimeException("Load class({$this->classname}) must be instanceof \think\Model");
        }

        $this->internalObject = $model;
        return $this->reflectionObject ??= new \ReflectionObject($this->internalObject);
    }

    public function getTableName(): ?string
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

    public function getPkValue(): string|array|null|false
    {
        if (!$this->hasFile()) {
            return false;
        }
        $obj = $this->makeReflectionInstance();
        if (!$obj->hasProperty('pk')) {
            return false;
        }
        $prop = $obj->getProperty('pk');
        $prop->setAccessible(true);
        return $prop->getValue($this->internalObject);
    }

    public function getConnectOptions(): ?array
    {
        $name = $this->getConnectName();

        if (empty($name)) {
            return null;
        }

        return app()->db->getConfig("connections.{$name}");

    }

    public function getConnectName(): string
    {
        if (null !== $this->connectName) {
            return $this->connectName;
        }

        $prop = $this->makeReflectionInstance()->getProperty('connection');
        $prop->setAccessible(true);

        return $this->connectName ??= ($prop->getValue($this->internalObject) ?: $this->options->getConnect());
    }

    public function getFileContent(): string
    {
        return file_get_contents($this->pathname);
    }

    public function writeFileContent(string $content): bool
    {
        $flags = LOCK_EX;
        if (\defined('TEST_MODEL_GENERATOR_USE_VFS') && TEST_MODEL_GENERATOR_USE_VFS && str_starts_with($this->pathname, 'vfs://')) {
            $flags &= ~LOCK_EX;
        }
        return file_put_contents($this->pathname, $content, $flags) > 0;
    }
}
