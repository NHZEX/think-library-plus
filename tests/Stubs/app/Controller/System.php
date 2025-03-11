<?php

namespace Tests\Stubs\app\Controller;

use Tests\Stubs\Middleware\ThrottleMiddlewareMock;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Middleware;
use Zxin\Think\Route\Annotation\Route;

#[Group('system')]
class System extends ApiBase
{
    /**
     * 基本系统设置.
     */
    #[Route(method: 'GET')]
    public function config()
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
     * 获取一个验证码
     */
    #[Route(method: 'GET', middleware: [])]
    #[Middleware(ThrottleMiddlewareMock::class, [
        ['visit_rate' => '10/m'],
    ])]
    public function captcha()
    {
    }

    /**
     * 重置缓存.
     */
    #[Auth('admin.resetCache')]
    #[Route(method: 'GET')]
    public function resetCache()
    {
    }
}
