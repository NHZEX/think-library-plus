<?php

declare(strict_types=1);

namespace Zxin\Think\Auth;

/**
 * Trait InteractsWithStorage
 */
trait InteractsWithStorage
{
    protected function build(): array
    {
        $permissionList = $this->fillPermission($this->permissions, []);

        $permission = Permission::getInstance();
        if ($permission->hasStorage()) {
            $permissionMetaItems = $this->permissionMetaItems;
            foreach ($permissionList as $key => $item) {
                if ($info = $permission->queryPermission($key)) {
                    $item['sort'] = $permissionMetaItems[$key]['sort'] ?? (int) $info['sort'];
                    $item['desc'] = $permissionMetaItems[$key]['desc'] ?? $info['desc'];
                    $permissionList[$key] = $item;
                }
            }
        }

        $permission2features = [];
        foreach ($permissionList as $pKey => $data) {
            $permission2features[$pKey] = array_merge(
                $permission2features[$pKey] ?? [],
                $data['allow'] ?? []
            );
        }

        $features2permission = [];
        foreach ($permission2features as $permission => $features) {
            foreach ($features as $feature) {
                $features2permission[$feature][$permission] = true;
            }
        }

        return [
            'features'   => $this->nodes,
            'permission' => $permissionList,
            'permission2features' => $permission2features,
            'features2permission' => $features2permission,
        ];
    }

    /**
     * @return array<string, array>
     */
    protected function fillPermission(array $data, array $original): array
    {
        $result = [];
        $permissionMetaItems = $this->permissionMetaItems;
        $original = $original['permission'] ?? [];
        foreach ($data as $permission => $control) {
            // 填充父节点
            $pid = $this->fillParent($result, $original, $permission);
            // 生成插入数据
            if (isset($original[$permission])) {
                $sort = $original[$permission]['sort'];
                $desc = $original[$permission]['desc'];
            } else {
                $sort = 0;
                $desc = '';
            }
            if (isset($control['desc']) || isset($control['allow'])) {
                $result[$permission] = [
                    'pid' => $pid,
                    'name' => $permission,
                    'sort' => $permissionMetaItems[$permission]['sort'] ?? $control['sort'] ?? $sort,
                    'desc' => $permissionMetaItems[$permission]['desc'] ?? $control['desc'] ?? $desc,
                    'allow' => $control['allow'] ?? null,
                ];
            } else {
                $result[$permission] = [
                    'pid' => $pid,
                    'name' => $permission,
                    'sort' => $permissionMetaItems[$permission]['sort'] ?? $sort,
                    'desc' => $permissionMetaItems[$permission]['desc'] ?? $desc,
                    'allow' => array_values($control),
                ];
            }
        }

        ksort($result);
        return $result;
    }


    /**
     * 填充父节点
     */
    protected function fillParent(array &$data, array $original, string $permission): string
    {
        $delimiter = '.';
        $parents = explode($delimiter, $permission);
        if (1 === \count($parents)) {
            return self::ROOT_NODE;
        }
        array_pop($parents);
        $result = implode($delimiter, $parents);

        $permissionMetaItems = $this->permissionMetaItems;

        while (\count($parents)) {
            $curr = implode($delimiter, $parents);
            array_pop($parents);
            $parent = implode($delimiter, $parents) ?: self::ROOT_NODE;

            if (isset($original[$curr])) {
                $sort = $original[$curr]['sort'];
                $desc = $original[$curr]['desc'];
            } else {
                $sort = 0;
                $desc = '';
            }
            $data[$curr] = [
                'pid' => $parent,
                'name' => $curr,
                'sort' => $permissionMetaItems[$curr]['sort'] ?? $sort,
                'desc' => $permissionMetaItems[$curr]['desc'] ?? $desc,
                'allow' => $data[$curr]['allow'] ?? null,
            ];
        }

        return $result;
    }
}
