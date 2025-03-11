<?php
declare(strict_types=1);

use think\App;

require __DIR__ . '/../vendor/autoload.php';

const TEST_MODEL_GENERATOR_USE_VFS = true;

require __DIR__ . '/VFSStructure/GeneratorRootStructure.php';

$app = new App(__DIR__ . DIRECTORY_SEPARATOR . 'Stubs');
$app->setNamespace('Tests\\Stubs\\app');
$app->initialize();

//$app->config->set([], 'cache');

echo 'think framework version: ', $app->version(), PHP_EOL;

