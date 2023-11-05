<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use think\App;
use Zxin\Think\Model\ModelGenerator\ModelGeneratorService;
use Zxin\Think\Model\ModelGenerator\Options\DefaultConfigOptions;
use Zxin\Think\Model\ModelGenerator\TableCollection;

class ModelGeneratorTest extends TestCase
{
    protected App $app;

    protected function setUp(): void
    {
        $this->app = \app();
    }

    public static function echoLogger(): LoggerInterface
    {
        return (new class() extends AbstractLogger {
            protected array $logs = [];

            public function getLogs(): array
            {
                return $this->logs;
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                echo \sprintf('%s: %s', $level, $message), PHP_EOL;
            }
        });
    }

    public function testCreateModel(): void
    {
        $logger = self::echoLogger();

        $defaultOptions = DefaultConfigOptions::makeDefault(
            connect: 'main',
            namespace: 'Tests\\ModelOutput',
            baseClass: 'Tests\\ModelOutput\\ModelBase',
            exclude: [],
        );

        $this->app->db->connect($defaultOptions->getConnect())->execute('DROP TABLE IF EXISTS `test_admin_user`');
        $this->app->db->connect($defaultOptions->getConnect())->execute(<<<MYSQL
            create table if not exists test_admin_user
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
            ) comment '系统用户' collate = utf8mb4_general_ci;
            MYSQL
        );

        $tableCollection = new TableCollection(
            defaultOptions: $defaultOptions,
            mapping: [],
            logger: $logger,
            tryRun: true,
        );

        $tableCollection->loadTables();

        $record = $tableCollection->createModel($defaultOptions->getConnect(), 'test_admin_user', '系统用户456');

        echo $record->getContent(), PHP_EOL;

        self::assertNotNull($record);
        self::assertEquals(<<<PHP
            <?php
            
            declare(strict_types=1);
            
            namespace Tests\ModelOutput;
            
            /**
             * Model: 系统用户456.
             *
             * @property int    \$id
             * @property int    \$genre           用户类型
             * @property int    \$status          用户状态
             * @property string \$username        用户账户
             * @property string \$nickname        用户昵称
             * @property string \$password        用户密码
             * @property string \$email           用户邮箱
             * @property string \$avatar          用户头像
             * @property int    \$role_id         角色ID
             * @property int    \$group_id        部门ID
             * @property string \$signup_ip       注册IP
             * @property int    \$create_time     创建时间
             * @property int    \$update_time     更新时间
             * @property int    \$delete_time     删除时间
             * @property int    \$last_login_time 最后登录时间
             * @property string \$last_login_ip   登录ip
             * @property string \$remember        登录ip
             * @property int    \$lock_version
             */
            final class TestAdminUserModel extends ModelBase
            {
                public \$table = 'test_admin_user';
                public \$pk = 'id';
            }
            
            PHP,
            $record->getContent(),
        );
    }

    public function testScanGenerator(): void
    {
        $logger = self::echoLogger();

        $mgs = new ModelGeneratorService($logger);

        $mgs->loadConfig();
        $tableResult = $mgs->execute(true);

        $recordRows = $tableResult->getRecordRows();

        self::assertNotEmpty($recordRows);

        $rootPath = \rtrim(\app()->getRootPath(), '/') . '/';

        foreach ($recordRows as $row) {
            echo \sprintf(
                '> [%s]%s, class %s, filename: %s, status: %s',
                $row->getConnect(),
                $row->getTable(),
                $row->getClassName(),
                $row->getFilename($rootPath),
                $row->getStatus(),
            ), PHP_EOL;

            if ($row->getStatus() === 'CREATE') {
                continue;
            }
            // UNCHANGED
            if ($this->getStatus() === 'UPDATE') {
                self::assertEquals(\file_get_contents($row->getFilename()), $row->getContent());
                continue;
            }
        }
    }
}
