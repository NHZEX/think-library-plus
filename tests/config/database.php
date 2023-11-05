<?php
declare(strict_types=1);

return [
    // 默认使用的数据库连接配置
    'default' => 'main',

    // 自定义时间查询规则
    'time_query_rule' => [],

    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp' => true,

    // 时间字段取出后的默认时间格式
    'datetime_format' => false,

    // 数据库连接配置信息
    'connections' => [
        'main' => [
            // 数据库类型
            'type'            => 'mysql',
            // 服务器地址
            'hostname'        => env('DB_MAIN_HOSTNAME', '127.0.0.1'),
            // 端口
            'hostport'        => env('DB_MAIN_HOSTPORT', '3306'),
            // 数据库名
            'database'        => (string) env('DB_MAIN_DATABASE', ''),
            // 用户名
            'username'        => (string) env('DB_MAIN_USERNAME', 'root'),
            // 密码
            'password'        => (string) env('DB_MAIN_PASSWORD', ''),
            // 数据库编码默认采用utf8
            'charset'         => 'utf8mb4',
            // 数据库表前缀
            'prefix'          => '',
        ],

        // 更多的数据库配置信息
        'cat' => [
            // 数据库类型
            'type'            => 'mysql',
            // 服务器地址
            'hostname'        => env('DB_MAIN_HOSTNAME', '127.0.0.1'),
            // 端口
            'hostport'        => env('DB_MAIN_HOSTPORT', '3306'),
            // 数据库名
            'database'        => (string) env('DB_MAIN_DATABASE', ''),
            // 用户名
            'username'        => (string) env('DB_MAIN_USERNAME', 'root'),
            // 密码
            'password'        => (string) env('DB_MAIN_PASSWORD', ''),
            // 数据库编码默认采用utf8
            'charset'         => 'utf8mb4',
            // 数据库表前缀
            'prefix'          => '',
        ],
    ],
];