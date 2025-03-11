<?php

namespace Tests\Stubs\App\Controller\V2;

use Tests\Stubs\App\Controller\ApiBase;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Route;

#[Group('v2/system')]
class System extends ApiBase
{
    /**
     * 基本系统设置.
     */
    #[Route(method: 'GET')]
    public function config()
    {
    }

    #[Auth]
    #[Route(method: 'GET')]
    public function info()
    {
    }

    #[Auth()]
    #[Route(method: 'GET')]
    public function sysinfo()
    {
    }

    #[Auth('admin')]
    #[Route(method: 'GET')]
    public function database()
    {
    }

    /**
     * 重置缓存.
     */
    #[Auth('admin.resetCache')]
    #[Route(method: 'POST')]
    public function resetCache()
    {
    }
}
