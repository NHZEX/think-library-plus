<?php

namespace Zxin\Think\Validate;

use think\App;
use think\exception\HttpException;
use think\Request;
use think\Response;
use think\Validate;
use Zxin\Think\Validate\Annotation\Validation;

class ValidateMiddleware
{
    use InteractsWithAnnotation;

    /**
     * 验证器映射
     */
    protected array $mapping = [];

    protected array $config = [];

    protected string $namespace;

    protected ?string $errorHandle;

    protected App $app;

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $this->app = app();
        if (is_file($path = ValidateService::getDumpFilePath())) {
            $this->mapping = require $path;
        }
        $this->namespace = $this->app->config->get('validate.namespace', 'app\\Validate');
        if (!str_ends_with($this->namespace, '\\')) {
            $this->namespace .= '\\';
        }
        $this->errorHandle = $this->app->config->get('validate.error_handle');
    }

    public function handle(Request $request, \Closure $next): Response
    {
        $controllerClass = $this->getControllerClassName($request);
        $controllerAction = $request->action(true);

        // 转存匹配
        $storage = $this->app->get('validateStorage');
        if (\is_array($storage) && ($v = $storage[$controllerClass][$controllerAction] ?? null)) {
            $result = $this->execValidate($request, $controllerClass, $controllerAction, $v['validate'], $v['scene']);
            if ($result instanceof Response) {
                return $result;
            } else {
                return $next($request);
            }
        }

        // 注解匹配
        $annotation = $this->parseAnnotation($controllerClass, $controllerAction);
        if ($annotation instanceof Validation) {
            $validateClass = $annotation->name;
            $validateScene = $annotation->scene;
            $result = $this->execValidate($request, $controllerClass, $controllerAction, $validateClass, $validateScene);
            if ($result instanceof Response) {
                return $result;
            }
        } else {
            return $this->compatible($request, $next, $controllerClass, $controllerAction);
        }
        return $next($request);
    }

    /**
     * @param             $controllerClass
     * @param             $controllerAction
     * @throws ValidateException
     */
    protected function execValidate(Request $request, $controllerClass, $controllerAction, string $class, ?string $scene): ?Response
    {
        if (is_subclass_of($class, AskValidateInterface::class)) {
            $result = $class::askValidate($request, $scene);
            if ($result) {
                if (\is_string($result)) {
                    $class = $result;
                } elseif (\is_array($result) && \count($result) > 1) {
                    $class = $result[0];
                    $scene = $result[1] ?? null;
                }
            }
        }
        /** @var Validate $validateClass */
        $validateClass = new $class();
        if ($scene) {
            // 自行决定使用何种场景
            if (
                '?' === $scene
                && ($validateClass instanceof AskSceneInterface || method_exists($validateClass, 'askScene'))
            ) {
                $scene = $validateClass->askScene($request) ?: false;
            }
            // 选中验证场景
            $scene && $validateClass->scene($scene);
        }
        if ($this->app->isDebug()) {
            $this->app->log->record(\sprintf('[validate] %s, scene=%s', $validateClass::class, $scene ?: 'null'), 'debug');
        }
        $input = $request->param();
        if ($files = $request->file()) {
            $input += $files;
        }
        if (false === $validateClass->check($input)) {
            $ctx = ValidateContext::create($controllerClass, $controllerAction, $validateClass, false, []);
            if ($this->errorHandle) {
                /** @var ErrorHandleInterface|object $errorHandle */
                $errorHandle = $this->app->make($this->errorHandle);
                if (!($errorHandle instanceof ErrorHandleInterface)) {
                    throw new ValidateException('errorHandle not implement ' . ErrorHandleInterface::class);
                }
                return $errorHandle->handle($request, $ctx);
            }
            $message = \is_array($validateClass->getError()) ? join(',', $validateClass->getError()) : $validateClass->getError();
            return Response::create($message, 'html', 400);
        }
        $allowInputFields = [];
        if ($validateClass instanceof ValidateBase) {
            $allowInputFields = $validateClass->getRuleKeys();
        } elseif (method_exists($validateClass, 'getRuleKeys')) {
            throw new ValidateException(
                \sprintf('Must extends the %s class', ValidateBase::class)
            );
        }
        ValidateContext::create($controllerClass, $controllerAction, $validateClass, true, $allowInputFields);
        return null;
    }

    /**
     * 兼容匹配
     * @param         $controllerClass
     * @param         $controllerAction
     */
    protected function compatible(Request $request, \Closure $next, $controllerClass, $controllerAction): Response
    {
        if (!isset($this->mapping[$controllerClass])) {
            return $next($request);
        }
        $validateCfg = array_change_key_case($this->mapping[$controllerClass])[$controllerAction] ?? false;
        if (\is_array($validateCfg)) {
            // 解析验证配置
            $validateCfg = array_pad($validateCfg, 3, null);
            if (\is_string($validateCfg[0])) {
                [$validateClass, $validateScene] = $validateCfg;
            } else {
                [, $validateClass, $validateScene] = $validateCfg;
            }

            // 验证输入数据
            if ($validateClass && class_exists($validateClass)) {
                $result = $this->execValidate($request, $controllerClass, $controllerAction, $validateClass, $validateScene);
                if ($result instanceof Response) {
                    return $result;
                }
            }
        }

        return $next($request);
    }

    protected function getControllerClassName(Request $request): ?string
    {
        $suffix = $this->app->route->config('controller_suffix') ? 'Controller' : '';
        $controllerLayer = $this->app->route->config('controller_layer') ?: 'controller';

        $name = $request->controller();
        $class = $this->app->parseClass($controllerLayer, $name . $suffix);
        if (!class_exists($class)) {
            throw new HttpException(404, 'controller not exists:' . $class);
        }

        return $class;
    }
}
