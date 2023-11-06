<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Zxin\Think\Model\ModelGenerator\ModelGeneratorHelper;

class ModelGeneratorHelperTest extends TestCase
{
    public function testClassToPath(): void
    {
        $this->assertEquals(__FILE__, \realpath(ModelGeneratorHelper::classToPath('Tests\\ModelGeneratorHelperTest')));
    }
}
