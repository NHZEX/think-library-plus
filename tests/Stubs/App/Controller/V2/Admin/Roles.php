<?php

declare(strict_types=1);

namespace Tests\Stubs\App\Controller\V2\Admin;

use Tests\Stubs\App\Controller\admin\Base;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthMeta;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Resource;
use Zxin\Think\Validate\Annotation\Validation;

#[Group('v2/admin', registerSort: 2900)]
#[Resource('roles')]
class Roles extends Base
{
    #[Auth('admin.role.info')]
    #[AuthMeta('获取角色信息')]
    public function index(int $limit = 1)
    {
    }

    #[Auth('admin.role.info')]
    #[Auth('admin.user')]
    #[AuthMeta('获取角色信息')]
    public function select($genre = 0)
    {
    }

    #[Auth('admin.role.info')]
    #[AuthMeta('获取角色信息')]
    public function read(int $id)
    {
    }

    #[Auth('admin.role.add')]
    #[AuthMeta('创建角色信息')]
    #[Validation('@Admin.Role')]
    public function save()
    {
    }

    #[Auth('admin.role.edit')]
    #[AuthMeta('更改角色信息')]
    #[Validation('@Admin.Role')]
    public function update($id)
    {
    }

    #[Auth('admin.role.del')]
    #[AuthMeta('删除角色信息')]
    public function delete($id)
    {
    }
}
