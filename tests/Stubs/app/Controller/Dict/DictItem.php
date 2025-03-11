<?php

declare(strict_types=1);

namespace Tests\Stubs\app\Controller\Dict;

use Tests\Stubs\app\Controller\ApiBase;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthDesc;
use Zxin\Think\Route\Annotation\Resource;

#[Resource('dict/:dictId', pattern: ['dictId' => '\d+'], presetFilter: [false, 'index'])]
#[Resource('dict-item', presetFilter: ['read', 'save', 'update', 'delete'])]
#[AuthDesc([
    'dict.item' => ['字典项目', 104],
    'dict.item.read' => ['读取项目', 111],
    'dict.item.edit' => ['编辑项目', 112],
    'dict.item.delete' => ['删除项目', 113],
])]
class DictItem extends ApiBase
{
    #[Auth('dict.read')]
    #[Auth('dict.item.read')]
    public function read(int $id)
    {
    }

    #[Auth('dict.item.edit')]
    public function save(int $dictId)
    {
    }

    #[Auth('dict.item.edit')]
    public function update(int $id, ?int $dictId = null)
    {
    }

    #[Auth('dict.item.delete')]
    public function delete(int $id)
    {
    }
}
