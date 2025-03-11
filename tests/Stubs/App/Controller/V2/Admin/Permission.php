<?php

namespace Tests\Stubs\App\Controller\V2\Admin;

use Tests\Stubs\App\Controller\admin\Base;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Route;

#[Group('v2/admin/permission')]
class Permission extends Base
{
    #[Auth('admin.permission.info')]
    #[Route('tree', method: 'GET')]
    public function index()
    {
    }

    #[Auth('admin.permission.scan')]
    #[Route('scan', method: 'POST')]
    public function scan()
    {
    }

    #[Auth('admin.permission.info')]
    #[Route(':id', method: 'GET', pattern: ['id' => '\S+'])]
    public function read()
    {
    }

    #[Auth('admin.permission.edit')]
    #[Route(':id', method: 'PUT', pattern: ['id' => '\S+'])]
    public function update()
    {
    }
}
