<?php
declare(strict_types=1);

return [
    'baseClass'      => \think\Model::class,
    'baseNamespace'  => 'app\\Model',
    'defaultConnect' => null,
    'strictTypes'    => true,

    'exclude' => [
        '_phinxlog',
    ],

    'single' => [
        [
            'table' => 'table_name',
            'class' => 'class_name',
        ],
    ],

    'mapping' => [
        [
            'table'     => [
                'admin_*',
            ],
            'connect'   => null,
            'namespace' => 'app\\Model\\Admin',
        ],
    ],
];
