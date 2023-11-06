<?php
declare(strict_types=1);

namespace Tests\VFSStructure;

const SCAN_DDL = <<<MYSQL
    create table if not exists _phinxlog
    (
        version        bigint               not null
            primary key,
        migration_name varchar(100)         null,
        start_time     timestamp            null,
        end_time       timestamp            null,
        breakpoint     tinyint(1) default 0 not null
    )
        charset = utf8mb3;
    
    create table if not exists activity_log
    (
        id           int unsigned auto_increment
            primary key,
        user_id      int unsigned               default '0' not null comment '用户ID',
        create_time  int unsigned               default '0' not null comment '创建时间',
        auth_name    varchar(64) charset ascii  default ''  not null,
        target       varchar(255) charset ascii default ''  not null,
        method       varchar(8) charset ascii   default ''  not null,
        url          varchar(255)               default ''  not null,
        ip           varchar(40) charset ascii  default ''  not null,
        http_code    smallint unsigned          default '0' not null,
        resp_code    varchar(32) charset ascii  default ''  not null,
        resp_message text                                   not null,
        details      json                                   null
    )
        comment '活动日志' collate = utf8mb4_general_ci;
    
    create table if not exists admin_role
    (
        id           int unsigned auto_increment
            primary key,
        pid          int unsigned     default '0' not null,
        genre        tinyint unsigned default '0' not null comment '角色类型',
        status       tinyint unsigned default '0' not null comment '角色状态',
        create_time  int unsigned     default '0' not null comment '创建时间',
        update_time  int unsigned     default '0' not null comment '更新时间',
        delete_time  int unsigned     default '0' not null comment '删除时间',
        name         varchar(32)      default ''  not null comment '角色名称',
        description  varchar(128)     default ''  not null comment '角色描述',
        ext          json                         not null comment '角色权限',
        lock_version int unsigned     default '0' not null
    )
        comment '系统角色' collate = utf8mb4_general_ci;
    
    create table if not exists admin_user
    (
        id              int unsigned auto_increment
            primary key,
        genre           tinyint unsigned          default '0' not null comment '用户类型',
        status          tinyint unsigned          default '0' not null comment '用户状态',
        username        varchar(32)               default ''  not null comment '用户账户',
        nickname        varchar(32)               default ''  not null comment '用户昵称',
        password        varchar(255)              default ''  not null comment '用户密码',
        email           varchar(64)               default ''  null comment '用户邮箱',
        avatar          varchar(96)               default ''  null comment '用户头像',
        role_id         int unsigned              default '0' not null comment '角色ID',
        group_id        int unsigned              default '0' not null comment '部门ID',
        signup_ip       varchar(46) charset ascii default ''  not null comment '注册IP',
        create_time     int unsigned              default '0' not null comment '创建时间',
        update_time     int unsigned              default '0' not null comment '更新时间',
        delete_time     int unsigned              default '0' not null comment '删除时间',
        last_login_time int unsigned              default '0' not null comment '最后登录时间',
        last_login_ip   varchar(46) charset ascii default ''  not null comment '登录ip',
        remember        varchar(16) charset ascii default ''  not null comment '登录ip',
        lock_version    int unsigned              default '0' not null,
        constraint idx_username
            unique (username, delete_time)
    )
        comment '系统用户' collate = utf8mb4_general_ci;
    
    create table if not exists attachment
    (
        id            int unsigned auto_increment
            primary key,
        status        tinyint unsigned           default '0' not null,
        driver        varchar(16) charset ascii  default ''  not null comment '文件驱动',
        `index`       varchar(64) charset ascii  default ''  not null comment '文件索引',
        uid           int unsigned               default '0' not null comment '操作用户',
        path          varchar(255)               default ''  not null comment '文件路径',
        mime          varchar(128) charset ascii default ''  not null comment 'mime类型',
        ext           char(8) charset ascii                  not null comment '文件后缀',
        size          int unsigned               default '0' not null comment '文件大小',
        sha1          char(40)                               not null comment 'SHA1',
        raw_file_name varchar(128)               default ''  not null comment '原始文件名',
        create_time   int unsigned               default '0' not null comment '创建时间',
        update_time   int unsigned               default '0' not null comment '更新时间'
    )
        comment '附件管理' collate = utf8mb4_general_ci;
    
    create table if not exists exception_logs
    (
        id             int unsigned auto_increment
            primary key,
        create_time    int unsigned  default '0' not null comment '创建时间',
        request_url    varchar(255)  default ''  not null comment '请求地址',
        request_route  varchar(255)  default ''  not null comment '请求路由',
        request_method varchar(8)    default ''  not null comment '请求方法',
        request_ip     varchar(46)   default ''  not null comment '请求IP',
        mode           varchar(16)   default ''  not null comment '类型',
        request_info   text                      not null comment '请求信息',
        message        varchar(2048) default ''  not null comment '消息',
        trace_info     text                      not null comment '异常堆栈'
    )
        comment '异常堆栈日志' collate = utf8mb4_general_ci;
    
    create table if not exists `system`
    (
        label varchar(48) charset ascii default '' not null comment '标签'
            primary key,
        value varchar(255)              default '' not null comment '值',
        constraint uk_label
            unique (label)
    )
        comment '系统表' collate = utf8mb4_general_ci;
    
    create table if not exists user_role_relation
    (
        id          int unsigned auto_increment
            primary key,
        user_id     int not null,
        role_id     int not null,
        create_time int not null
    );
    MYSQL;


const SCAN_ROOT_DIR = [
    'ModelBase.php'                  => <<<PHP
        <?php
        declare(strict_types=1);
        
        namespace Tests\ModelOutput;
        
        use think\Model;
        
        abstract class ModelBase extends Model
        {
        
        }
        PHP,
    'UserRoleRelationModelAlias.php' => <<<PHP
        <?php
        
        declare(strict_types=1);
        
        namespace Tests\ModelOutput;
        
        /**
         * Model: Table of user_role_relation.
         * 
         * @property int \$id 
         * @property int \$user_id 
         * @property int \$role_id 
         * @property int \$create_time 
         */
        final class UserRoleRelationModelAlias extends ModelBase
        {
            public \$table = 'user_role_relation';
            public \$pk = 'id';
        }
        
        PHP,
    'T2'                             => [
        'T2ModelBase.php' => <<<PHP
            <?php
            declare(strict_types=1);
            
            namespace Tests\ModelOutput\T2;
            
            use think\Model;
            
            abstract class T2ModelBase extends Model
            {
            
            }
            PHP,
    ],
];