<?php

declare(strict_types=1);

namespace Tests\Stubs\App\Controller\V2;

use app\Validate\Login;
use Tests\Stubs\App\Controller\ApiBase;
use Tests\Stubs\Middleware\ThrottleMiddlewareMock;
use Zxin\Think\Auth\AuthGuard;
use Zxin\Think\Route\Annotation\Middleware;
use Zxin\Think\Route\Annotation\Route;
use Zxin\Think\Validate\Annotation\Validation;

class Index extends ApiBase
{
    /**
     * 获取一个验证码
     */
    #[Route('v2/captcha', method: 'GET', middleware: [])]
    #[Middleware(ThrottleMiddlewareMock::class, [
        ['visit_rate' => '10/m'],
    ])]
    public function captcha()
    {
    }

    #[Route('v2/login', method: 'GET')]
    public function loginConfig()
    {
    }

    #[Validation(Login::class)]
    #[Route('v2/login', method: 'POST')]
    public function login()
    {
    }

    #[Route('v2/logout', method: 'GET')]
    public function logout(AuthGuard $auth)
    {
    }
}
