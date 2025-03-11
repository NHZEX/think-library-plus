<?php

declare(strict_types=1);

namespace Tests\Stubs\App\Controller\V2\Admin;

use Tests\Stubs\App\Controller\admin\Base;
use Tests\Stubs\App\Validate\Admin\User;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthMeta;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Resource;
use Zxin\Think\Route\Annotation\ResourceRule;
use Zxin\Think\Validate\Annotation\Validation;

#[Group('v2/admin', registerSort: 3000)]
#[Resource('users')]
class Users extends Base
{
    #[Auth('admin.user.info')]
    #[AuthMeta('获取用户信息')]
    public function index(int $limit = 1)
    {
    }

    #[Auth("login")]
    public function select()
    {
    }

    #[Auth('admin.user.info')]
    #[AuthMeta('获取用户信息')]
    public function read(int $id)
    {
    }

    #[Auth('admin.user.add')]
    #[AuthMeta('添加用户信息')]
    #[Validation(name: User::class, scene: '_')]
    public function save()
    {
    }

    #[Auth('admin.user.edit')]
    #[AuthMeta('更改用户信息')]
    #[Validation(name: User::class, scene: '_')]
    public function update(int $id)
    {
    }

    #[Auth('admin.user.reset-password')]
    #[AuthMeta('重置用户密码')]
    #[ResourceRule(':id/reset-password', 'POST')]
    #[Validation(name: User::class, scene: 'resetPasswod')]
    public function resetPassword(int $id)
    {
    }

    #[Auth('admin.user.del')]
    #[AuthMeta('删除用户信息')]
    public function delete(int $id)
    {
    }
}
