<?php

declare(strict_types=1);

namespace Tests\Annotation;

use PHPUnit\Framework\TestCase;
use Zxin\Think\Annotation\Core\DumpCommand;

final class DumpCommandTest extends TestCase
{
    public function testTextOutputStartsWithProtocolHint(): void
    {
        $output = DumpCommand::fromArgv(['dump-auth'])->render(
            'dump-auth',
            'AuthDump',
            'ok',
            true,
            '/tmp/auth_storage.php',
            12.34,
            ['controllers' => 2, 'methods' => 5, 'auth' => 3]
        );

        $lines = explode(PHP_EOL, trim($output));

        self::assertSame(
            '# dump-output v1 command=dump-auth format=text detail=summary supports=--format=json,--verbose,--quiet,--help',
            $lines[0]
        );
        self::assertStringContainsString(
            'AuthDump: ok changed=yes file=/tmp/auth_storage.php elapsed=12.34ms controllers=2 methods=5 auth=3',
            $lines[1]
        );
    }

    public function testJsonOutputIsPlainJson(): void
    {
        $output = DumpCommand::fromArgv(['dump-route', '--json'])->render(
            'dump-route',
            'RouteDump',
            'ok',
            false,
            '/tmp/route_storage.dump.php',
            1.2,
            ['controllers' => 1]
        );

        self::assertStringStartsNotWith('# dump-output', $output);

        $payload = json_decode($output, true);

        self::assertIsArray($payload);
        self::assertSame('dump-route', $payload['command']);
        self::assertSame('ok', $payload['status']);
        self::assertFalse($payload['changed']);
        self::assertSame(['controllers' => 1], $payload['summary']);
    }

    public function testVerboseTextOutputIncludesDetails(): void
    {
        $output = DumpCommand::fromArgv(['dump-auth', '--verbose'])->render(
            'dump-auth',
            'AuthDump',
            'ok',
            false,
            '/tmp/auth_storage.php',
            3,
            ['auth' => 1],
            [[
                'type' => 'auth',
                'method' => 'App\\Controller\\Index::index',
                'feature' => 'node@index/index',
            ]]
        );

        self::assertStringContainsString('format=text detail=verbose', $output);
        self::assertStringContainsString('details:', $output);
        self::assertStringContainsString('type=auth method=App\\Controller\\Index::index feature=node@index/index', $output);
    }

    public function testQuietOutputIsEmpty(): void
    {
        $output = DumpCommand::fromArgv(['dump-auth', '--quiet'])->render(
            'dump-auth',
            'AuthDump',
            'ok',
            false,
            '/tmp/auth_storage.php',
            1,
            ['auth' => 1]
        );

        self::assertSame('', $output);
    }
}
