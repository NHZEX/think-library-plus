<?php
declare(strict_types=1);

namespace Tests\ModelGenerator;

use PHPUnit\Framework\TestCase;
use Zxin\Think\Model\ModelGenerator\ModelGeneratorHelper;

class ModelGeneratorHelperTest extends TestCase
{
    public function testClassToPath(): void
    {
        $this->assertEquals(__FILE__, \realpath(ModelGeneratorHelper::classToPath(
            'Tests\\ModelGenerator\\ModelGeneratorHelperTest'
        )));
    }

    public function testNotExistClassToPath(): void
    {
        $this->assertStringEndsWith('/tests/T0/NotExistModel123.php', ModelGeneratorHelper::classToPath('\Tests\T0\NotExistModel123'));
    }
}
