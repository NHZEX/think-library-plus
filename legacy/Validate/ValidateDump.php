<?php

namespace Zxin\Think\Validate;

use Zxin\Think\Validate\Annotation\Validation;
use think\App;
use Zxin\Think\Annotation\Core\DumpValue;
use Zxin\Think\Annotation\Core\Scanning;

class ValidateDump
{
    use InteractsWithAnnotation;

    protected App $app;

    protected string $namespace;

    public static function dump()
    {
        echo '====== ValidateDump ======' . PHP_EOL;
        (new self())->scanAnnotation();
        echo '========== DONE ==========' . PHP_EOL;
    }

    public function __construct()
    {
        $this->app = app();

        $this->namespace = $this->app->config->get('validate.namespace', 'app\\Validate');
        if (!str_ends_with($this->namespace, '\\')) {
            $this->namespace .= '\\';
        }
    }

    public function scanAnnotation()
    {
        $scanning = new Scanning($this->app);
        $result = [];

        foreach ($scanning->scanningClass() as $class) {
            foreach (get_class_methods($class) as $method) {
                $validation = $this->parseAnnotation($class, $method);
                if (!$validation instanceof Validation) {
                    continue;
                }
                $validate = [
                    'validate' => $validation->name,
                    'scene' => empty($validation->scene) ? null : $validation->scene,
                ];
                $result[$class][$method] = $validate;
                echo "> {$class}@{$method}\t => {$validate['validate']}" . ($validate['scene'] ? "@{$validate['scene']}" : '') . PHP_EOL;
            }
        }

        $dump = new DumpValue(ValidateService::getDumpFilePath());
        $dump->load();
        $dump->save($result);
    }
}
