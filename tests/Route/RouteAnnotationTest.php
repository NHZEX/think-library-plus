<?php

declare(strict_types=1);

namespace Tests\Route;

use PHPUnit\Framework\TestCase;
use think\App;
use think\facade\Console;
use Zxin\Think\Route\RouteDump;
use Zxin\Think\Route\RouteLoader;

class RouteAnnotationTest extends TestCase
{
    protected App $app;

    protected function setUp(): void
    {
        $this->app = \app();
    }

    public function testDump()
    {
        RouteDump::dump();

        $filename = RouteLoader::getDumpFilePath();

        $newRouteDump = include $filename;

        $oldRouteDump = include __DIR__ . '/../data/route_storage.dump.php';

        self::assertEquals($oldRouteDump, $newRouteDump);
    }

    public function testLoader()
    {
        $loader = $this->app->make(RouteLoader::class);

        $loader->registerAnnotation();

        $output = Console::call('route:list', []);

        $routeListTxt = $output->fetch();
        echo $routeListTxt;

        $expected = file_get_contents(__DIR__ . '/../data/route_list.txt');

        $expected = preg_replace('/\\s+/', ' ', $expected); // 合并空白字符
        $routeListTxt = preg_replace('/\\s+/', ' ', $routeListTxt); // 合并空白字符

        self::assertEquals($expected, $routeListTxt);
    }
}
