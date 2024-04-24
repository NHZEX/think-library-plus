<?php
declare(strict_types=1);

namespace Tests;

use Composer\Autoload\ClassLoader;
use org\bovigo\vfs\vfsStream;
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

            public function log($level, string|\Stringable $message, array $context = []): void
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
            single: [],
            mapping: [],
            logger: $logger,
            tryRun: true,
        );

        $tableCollection->loadTables();

        $record = $tableCollection->createModel($defaultOptions->getConnect(), 'test_admin_user', '系统用户456');

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

        $this->app->db->connect($defaultOptions->getConnect())->execute('DROP TABLE IF EXISTS `test_admin_user`');
    }

    public function testScanGenerator(): void
    {
        $lines = \preg_split('#;\s*\n#', VFSStructure\SCAN_DDL);
        $lines = \array_map('\trim', $lines);
        $lines = \array_filter($lines);

        foreach ($lines as $line) {
            if (\str_starts_with($line, '#')) {
                continue;
            }
            $this->app->db->connect()->execute($line);
        }

        $fs = vfsStream::setup('MGScan', null, VFSStructure\SCAN_ROOT_DIR);

        $loaders = ClassLoader::getRegisteredLoaders();
        $loader = current($loaders);
        $loader->addPsr4('Tests\\ModelOutput\\', $fs->url(), true);

        $logger = self::echoLogger();

        $mgs = new ModelGeneratorService($logger);

        $mgs->loadConfig([
            // 映射时默认继承基类
            'baseClass'      => '\Tests\ModelOutput\ModelBase',
            // 映射时默认命名空间
            'baseNamespace'  => 'Tests\ModelOutput',
            // 映射时的默认连接，空表示使用db配置指定
            'defaultConnect' => null,
            // 生成文件是否使用严格类型
            'strictTypes'    => true,

            'exclude' => [
                '_phinxlog',
            ],

            // 单个模型绑定跟踪
            'single' => [
                [
                    'table'   => 'exception_logs',
                    'connect' => null,
                    'class'   => '\Tests\ModelOutput\T0\ExceptionLogsModelAlias',
                ],
                [
                    'table'   => 'exception_logs',
                    'connect' => null,
                    'class'   => '\Tests\ModelOutput\T0\ExceptionLogsModelNew',
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
                    'namespace' => 'Tests\ModelOutput\T2',
                ],
                [
                    // 匹配指定表，空代表任意表
                    'table'     => null,
                    // 模型映射关联的连接
                    'connect'   => 'cat',
                    // 模型映射使用的命名空间
                    'namespace' => 'Tests\\ModelOutput\\CAT',
                    'baseClass' => \Tests\Stubs\ModelGenCatBase::class,
                ],
            ],
        ]);
        $tableResult = $mgs->execute(false);

        $recordRows = $tableResult->getRecordRows();

        self::assertNotEmpty($recordRows);

        $hasExceptionLogsModelNew = false;
        $hasExceptionLogsModelAlias = false;
        foreach ($recordRows as $row) {
            echo \sprintf(
                '> [%s]%s, class %s, filename: %s, status: %s',
                $row->getConnect(),
                $row->getTable(),
                $row->getClassName(),
                $row->getFilename(),
                $row->getStatus(),
            ), PHP_EOL;

            self::assertTrue(\in_array($row->getStatus(), ['CREATE', 'UPDATE', 'LOSS', 'OK']));

            if ($row->getClassName() === 'Tests\ModelOutput\T0\ExceptionLogsModelNew') {
                // 通过直接映射新建类
                $hasExceptionLogsModelNew = true;
            } elseif ($row->getClassName() === 'Tests\ModelOutput\T0\ExceptionLogsModelAlias') {
                // 通过直接映射更新类
                $hasExceptionLogsModelAlias = true;
                self::assertEquals('OK', $row->getStatus());
            } elseif ($row->getClassName() === 'Tests\ModelOutput\LossModel') {
                self::assertEquals('LOSS', $row->getStatus());
            } elseif ($row->getStatus() === 'UPDATE') {
                self::assertEquals(\file_get_contents($row->getFilename()), $row->getContent());
            }
        }
        self::assertTrue($hasExceptionLogsModelNew, 'hasExceptionLogsModelNew');
        self::assertTrue($hasExceptionLogsModelAlias, 'hasExceptionLogsModelAlias');

        // 更新文件后测试UPDATE
        \file_put_contents($fs->url() . '/T2/AdminUserModel.php', VFSStructure\AdminUserModel_RAW);

        $tableResult = $mgs->execute(true);

        $recordRows = $tableResult->getRecordRows();

        self::assertNotEmpty($recordRows);

        foreach ($recordRows as $row) {
            echo \sprintf(
                '> [%s]%s, class %s, filename: %s, status: %s',
                $row->getConnect(),
                $row->getTable(),
                $row->getClassName(),
                $row->getFilename(),
                $row->getStatus(),
            ), PHP_EOL;

            if ($row->getClassName() === 'Tests\ModelOutput\LossModel') {
                self::assertEquals('LOSS', $row->getStatus());
            } elseif ($row->getClassName() === 'Tests\ModelOutput\T2\AdminUserModel') {
                self::assertEquals('UPDATE', $row->getStatus());
                self::assertEquals(VFSStructure\AdminUserModel_UPDATE, $row->getContent());
            } else {
                // UNCHANGED
                self::assertEquals('OK', $row->getStatus());
                self::assertEquals(\file_get_contents($row->getFilename()), $row->getContent());
            }
        }
    }
}
