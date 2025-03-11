<?php

namespace Tests\Stubs\app\Controller\admin;

use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Route;
use Zxin\Think\Validate\Annotation\Validation;

#[Group('admin', registerSort: 3000)]
class Index extends Base
{
    #[Validation('@Login')]
    #[Route(method: 'POST')]
    public function login()
    {
    }

    /**
     * 退出登陆.
     */
    #[Route(method: 'GET')]
    public function logout()
    {
    }

    /**
     * 获取用户信息.
     */
    #[Auth]
    #[Route('user-info', method: 'GET')]
    public function userInfo()
    {
    }
}
