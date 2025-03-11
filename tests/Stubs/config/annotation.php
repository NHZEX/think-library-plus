<?php

return [
    'restfull_definition' => [
        'index'  => ['get', '', 'index'],
        'select' => ['get', '/select', 'select'],
        'read'   => ['get', '/<id>', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/<id>', 'update'],
//        'patch'  => ['patch', '/<id>', 'patch'],
        'delete' => ['delete', '/<id>', 'delete'],
    ],
    'route' => [
        'dump_path' => \app()->getRootPath(),
        'only_load_dump' => false,
        'real_time_dump' => true,
    ],
];
