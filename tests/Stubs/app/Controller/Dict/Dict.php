<?php

declare(strict_types=1);

namespace Tests\Stubs\app\Controller\Dict;

use Tests\Stubs\app\Controller\ApiBase;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthDesc;
use Zxin\Think\Route\Annotation\Resource;
use Zxin\Think\Route\Annotation\ResourceRule;

#[Resource('dict')]
#[AuthDesc([
    'dict' => ['字典管理', 11],
    'dict.read' => ['读取字典', 101],
    'dict.edit' => ['编辑字典', 102],
    'dict.delete' => ['删除字典', 103],
    'dict.item-options' => ['字典选项[公共]', 104],
])]
class Dict extends ApiBase
{
    #[Auth('dict.read')]
    public function index(int $current = 1, int $size = 1)
    {
    }

    #[Auth([
        'dict.read',
        'dict.item-options',
        'common.business.options.dict',
    ])]
    public function read(int|string $id)
    {
    }

    #[Auth([
        'dict.read',
        'dict.item-options',
        'common.business.options.dict',
    ])]
    #[ResourceRule(':id/item')]
    public function item(int|string $id, int $current = 1, int $size = 10)
    {
    }

    #[Auth('dict.edit')]
    public function save()
    {
    }

    #[Auth('dict.edit')]
    public function update(int $id)
    {
    }

    #[Auth('dict.delete')]
    public function delete()
    {
    }
}
