<?php

namespace Tests\Stubs\App\Controller\admin;

use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthMeta;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Resource;
use Zxin\Think\Validate\Annotation\Validation;

/**
 * Class User.
 */
#[Group('admin', registerSort: 3000)]
#[Resource('users')]
class User extends Base
{
    #[Auth('admin.user.info')]
    #[AuthMeta('获取用户信息')]
    public function index(int $limit = 1)
    {
    }

    #[Auth('admin.user.info')]
    #[AuthMeta('获取用户信息')]
    public function read(int $id)
    {
    }

    #[Auth('admin.user.add')]
    #[AuthMeta('添加用户信息')]
    #[Validation(name: '@Admin.User', scene: '_')]
    public function save()
    {
    }

    #[Auth('admin.user.edit')]
    #[AuthMeta('更改用户信息')]
    #[Validation(name: '@Admin.User', scene: '_')]
    public function update(int $id)
    {
    }

    #[Auth('admin.user.del')]
    #[AuthMeta('删除用户信息')]
    public function delete(int $id)
    {
    }
}
