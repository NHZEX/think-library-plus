<?php

declare(strict_types=1);

namespace Tests\Stubs\app\Validate\Admin;

use Tests\Stubs\app\Validate\Base;

class Role extends Base
{
    // todo genre、status 从模型获取有效范围
    protected $rule = [
        'genre' => 'number',
        'status' => 'require|number',
        'name' => 'require|length:3,64',
        'ext' => 'array',
        'lock_version' => 'number',
    ];
}
