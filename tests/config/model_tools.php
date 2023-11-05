<?php
declare(strict_types=1);

// 模型生成路径自动使用 Psr4 规范映射，确保命名空间都已经声明

return [
    // 映射时默认继承基类
    'baseClass'      => \Tests\ModelOutput\ModelBase::class,
    // 映射时默认命名空间
    'baseNamespace'  => 'Tests\\ModelOutput',
    // 映射时的默认连接，空表示使用db配置指定
    'defaultConnect' => null,
    // 生成文件是否使用严格类型
    'strictTypes'    => true,

    'exclude' => [
        '_phinxlog',
    ],

    // 单个模型绑定跟踪
    'single' => [
//        [
//            'table'   => 'activity_log',
//            'connect' => null,
//            'class'   => \app\Service\Auth\Record\RecordModel::class,
//        ],
        // todo 有问题需要修
        [
            'table' => 'activity_log',
            'class' => \app\Service\Auth\Record\RecordTestModel::class,
        ],
    ],

    // 批量模型绑定跟踪
    'mapping' => [
        [
            // 匹配指定表
            'table'     => [
                'admin_*',
                'user_role_*',
            ],
            'exclude'   => [
                'activity_log',
            ],
            'namespace' => 'Tests\\ModelOutput',
        ],
        [
            // 匹配指定表，空代表任意表
            'table'     => null,
            // 模型映射关联的连接
            'connect'   => 'cat',
            // 模型映射使用的命名空间
            'namespace' => 'Tests\\ModelOutput\\T2',
            'baseClass' => \Tests\ModelOutput\T2\T2ModelBase::class,
        ],
    ],
];
