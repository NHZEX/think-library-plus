<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator;

use Composer\InstalledVersions;

class Helper
{
    private static bool $ormIsV4;

    public static function ormIsV4(): bool
    {
        return self::$ormIsV4 ??= version_compare(InstalledVersions::getVersion('topthink/think-orm'), '4.0', '>=');
    }
}
