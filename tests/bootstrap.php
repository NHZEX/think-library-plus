<?php
declare(strict_types=1);

use think\App;

require __DIR__ . '/../vendor/autoload.php';



$app = new App(__DIR__);
$app->initialize();

//$app->config->set([], 'cache');

echo 'think framework version: ', $app->version(), PHP_EOL;

