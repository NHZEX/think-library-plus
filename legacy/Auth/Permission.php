<?php

declare(strict_types=1);

namespace Zxin\Think\Auth;

use think\App;
use Zxin\Think\Auth\Exception\AuthException;

class Permission
{
    protected ?AuthStorage $storage = null;

    public static function getInstance($reset = false): Permission
    {
        $app = App::getInstance();
        if ($reset) {
            $app->delete(Permission::class);
        }
        return $app->make(Permission::class);
    }

    public static function getDumpFilePath(string $filename = 'auth_storage.php'): string
    {
        $path = App::getInstance()->config->get('auth.dump_file_path');
        if (empty($path)) {
            $path = App::getInstance()->getAppPath();
        }
        $path = str_replace('\\', '/', $path);
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        if (!is_dir($path)) {
            throw new AuthException("{$path} does not exist");
        }
        return $path . $filename;
    }

    public function __construct()
    {
        $this->loadStorage();
    }

    /**
     * 获取树
     * @param callable|null $filter
     */
    public function getTree(
        string $index = '__ROOT__',
        int $level = 0,
        ?array $data = null,
        callable $filter = null,
        bool $convertChildrenEmptyToNull = false,
    ): array {
        // todo 实现 key-mapping (label, key, isLeaf)

        if (null === $data) {
            $data = array_merge([], $this->loadStorage()->permission);
            if (\is_callable($data)) {
                $data = $filter($data);
            }
            usort($data, fn ($a, $b) => $a['sort'] <=> $b['sort']);
        }
        $tree = [];
        foreach ($data as $permission) {
            if ($permission['pid'] === $index) {
                $permission['title'] = $permission['name'];
                $permission['level'] = $level;
                $permission['spread'] = true;
                $permission['valid'] = !empty($permission['allow']);
                $children = $this->getTree($permission['title'], $level + 1, $data);
                if ($convertChildrenEmptyToNull && empty($children)) {
                    $children = null;
                }
                $permission['children'] = $children;
                $permission['isLeaf'] = empty($children);
                $tree[] = $permission;
            }
        }
        return $tree;
    }

    protected function loadStorage(): ?AuthStorage
    {
        if (empty($this->storage)) {
            $filename = Permission::getDumpFilePath();
            $this->storage = new AuthStorage(require $filename);
        }
        return $this->storage;
    }

    public function hasStorage(): bool
    {
        return $this->loadStorage() instanceof AuthStorage;
    }

    public function getStorage(): AuthStorage
    {
        return $this->storage;
    }

    public function getPermission(): array
    {
        return $this->loadStorage()->permission;
    }

    public function setPermission(array $permission): void
    {
        $this->loadStorage()->permission = $permission;
    }

    /**
     * 查询节点
     */
    public function queryFeature(string $node): ?array
    {
        return $this->loadStorage()->features[$node] ?? null;
    }

    /**
     * 查询权限
     */
    public function queryPermission(string $name): ?array
    {
        return $this->loadStorage()->permission[$name] ?? null;
    }

    public function getPermissionsByFeature($feature): ?array
    {
        return $this->loadStorage()->fe2pe[$feature] ?? null;
    }

    public function allPermission()
    {
        return $this->loadStorage()->pe2fe;
    }

    public function contain(string $node)
    {
        $storage = $this->loadStorage();
        return isset($storage->pe2fe[$node]) || isset($storage->fe2pe[$node]);
    }
}
