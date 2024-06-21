<?php
declare(strict_types=1);

return [
    'baseClass'        => \think\Model::class,
    'baseNamespace'    => 'app\\Model',
    'defaultConnect'   => null,
    'strictTypes'      => true,
    'fieldToCamelCase' => null,

    'exclude' => [
        '_phinxlog',
    ],

    // 单个模型绑定跟踪
    'single' => [
        [
            'table' => 'table_name',
            'class' => 'class_name',
            'fieldToCamelCase' => null,
        ],
    ],

    // 批量模型绑定跟踪
    'mapping' => [
        [
            'table'     => [
                'admin_*',
            ],
            'connect'   => null,
            'namespace' => 'app\\Model\\Admin',
            'fieldToCamelCase' => null,
        ],
    ],
];
