<?php

namespace Zxin\Think\Validate;

use think\Request;

interface AskSceneInterface
{
    /**
     * 询问当前应当使用何种场景
     */
    public static function askScene(Request $request): ?string;
}
