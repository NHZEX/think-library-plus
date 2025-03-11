<?php

namespace Tests\Stubs\App\Controller\admin;

use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\AuthScan;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Resource;
use Zxin\Think\Route\Annotation\ResourceRule;

#[Group('admin')]
#[Resource('permission')]
class Permission extends Base
{
    #[Auth('admin.permission.info')]
    public function index()
    {
    }

    #[Auth('admin.permission.info')]
    public function read()
    {
    }

    #[Auth('admin.permission.edit')]
    public function update()
    {
    }

    #[Auth('admin.permission.scan')]
    #[ResourceRule('scan', method: 'GET')]
    public function scan(AuthScan $authScan)
    {
    }

    private function allowAccess()
    {
    }
}
