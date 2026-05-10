<?php

declare(strict_types=1);

namespace Zxin\Think\Auth;

use Brick\VarExporter\VarExporter;
use think\App;
use Zxin\Think\Annotation\Core\DumpValue;
use Zxin\Think\Annotation\Core\Scanning;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthDesc;
use Zxin\Think\Auth\Annotation\AuthMeta;
use Zxin\Think\Auth\Annotation\Base;
use Zxin\Think\Auth\Exception\AuthException;

class AuthScan
{
    use InteractsWithStorage;

    public const ROOT_NODE = '__ROOT__';

    /**
     * @var App
     */
    protected $app;

    /**
     * @var Permission
     */
    protected $permission;

    protected $permissions = [];
    protected $nodes       = [];
    protected $controllers = [];
    protected array $permissionMetaItems = [];
    /**
     * @var array<string, int>
     */
    protected array $summary = [];
    /**
     * @var list<array<string, scalar|null>>
     */
    protected array $details = [];

    protected $debug = false;

    /**
     * AuthScan constructor.
     */
    public function __construct(App $app)
    {
        $this->app = $app;

        $this->permission = Permission::getInstance();
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function refresh()
    {
        $this->refreshWithReport();
    }

    /**
     * @return array{data: array, summary: array<string, int>, details: list<array<string, scalar|null>>}
     */
    public function refreshWithReport(): array
    {
        $this->scanAnnotation();

        $output = $this->build();
        $this->export($output);

        $summary = $this->summary + $this->summarizeStorage($output);

        return [
            'data' => $output,
            'summary' => $summary,
            'details' => $this->details,
        ];
    }

    public function export($value)
    {
        $dump = new DumpValue(Permission::getDumpFilePath(), VarExporter::TRAILING_COMMA_IN_ARRAY);
        $dump->load();
        $dump->save($value);
    }

    public function loadDefaultPermissions()
    {
        $default           = App::getInstance()->config->get('auth.permissions', []);
        $this->permissions = array_merge($default, $this->permissions);
    }

    protected function scanAnnotation()
    {
        $this->permissions = [];
        $this->nodes       = [];
        $this->controllers = [];
        $this->permissionMetaItems = [];
        $this->details = [];
        $this->summary = [
            'controllers' => 0,
            'methods' => 0,
            'auth' => 0,
            'auth_meta' => 0,
            'auth_desc' => 0,
        ];

        $this->loadDefaultPermissions();

        $scanning = new Scanning($this->app);

        foreach ($scanning->scanningClass() as $class) {
            try {
                $refClass = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                throw new AuthException('load class fail: ' . $class, 0, $e);
            }
            if ($refClass->isAbstract() || $refClass->isTrait()) {
                continue;
            }
            ++$this->summary['controllers'];

            $namespaces      = $scanning->getControllerNamespaces();
            $controllerLayer = $scanning->getControllerLayer();
            // 是否多应用
            $isApp = (!str_starts_with($class, $namespaces . $controllerLayer));

            if ($isApp) {
                $controllerUrl = substr($class, \strlen($namespaces));
                $appPos        = strpos($controllerUrl, '\\');
                $appName       = substr($controllerUrl, 0, $appPos);
                $controllerUrl = substr($controllerUrl, $appPos + \strlen($controllerLayer . '\\') + 1);
                $controllerUrl = $appName . '/' . strtolower(str_replace('\\', '.', $controllerUrl));
            } else {
                $controllerUrl = substr($class, \strlen($namespaces . $controllerLayer . '\\'));
                $controllerUrl = strtolower(str_replace('\\', '.', $controllerUrl));
            }

            foreach ($refClass->getAttributes(Base::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $attribute = $attribute->newInstance();
                if ($attribute instanceof AuthDesc) {
                    $this->handleAuthDesc($attribute);
                }
            }

            foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $refMethod) {
                if ($refMethod->isStatic()) {
                    continue;
                }
                $methodName = $refMethod->getName();
                if (str_starts_with($methodName, '_')) {
                    continue;
                }
                ++$this->summary['methods'];

                $nodeUrl    = $controllerUrl . '/' . strtolower($methodName);
                $methodPath = $class . '::' . $methodName;

                foreach ($refMethod->getAttributes(Base::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                    $this->handleAttributes($attribute->newInstance(), $methodPath, $nodeUrl, $controllerUrl, $methodName);
                }

                $this->controllers[$class][$methodName] = $nodeUrl;
            }
        }
    }

    /**
     * @return void
     */
    protected function handleAttributes(Base $obj, string $methodPath, string $nodeUrl, string $controllerUrl, string $methodName)
    {
        if ($obj instanceof Auth) {
            $this->handleAuth($obj, $methodPath, $nodeUrl, $controllerUrl, $methodName);
        } elseif ($obj instanceof AuthMeta) {
            $this->handleAuthMeta($obj, $methodPath, $nodeUrl);
        }
    }

    protected function handleAuth(Auth $auth, string $methodPath, string $nodeUrl, string $controllerUrl, string $methodName): void
    {
        if (empty($auth->name)) {
            throw new AuthException('annotation value not empty(Auth): ' . $methodPath);
        }

        $authFlags = \is_string($auth->name) ? [$auth->name] : $auth->name;
        $features = "node@{$nodeUrl}";
        ++$this->summary['auth'];

        foreach ($authFlags as $authFlag) {
            $authStr  = $this->parseAuth($authFlag, $controllerUrl, $methodName);

            if (isset($this->permissions[$authStr]['allow']) && !\is_array($this->permissions[$authStr]['allow'])) {
                $this->permissions[$authStr]['allow'] = [];
            }
            $this->permissions[$authStr]['allow'][] = $features;
        }

        $this->details[] = [
            'type' => 'auth',
            'method' => $methodPath,
            'feature' => $features,
            'permissions' => implode(',', $authFlags),
        ];

        // 记录节点控制信息
        $this->nodes[$features] = [
            'class'  => $methodPath,
            'policy' => $auth->policy,
            'desc'   => $auth->desc ?? '',
        ];

        if ($this->debug) {
            echo \sprintf(
                '> %s%s%s  => %s',
                $methodPath,
                PHP_EOL,
                $features,
                PHP_EOL
            );
        }
    }

    protected function handleAuthMeta(AuthMeta $auth, string $methodPath, string $nodeUrl): void
    {
        if (empty($auth->desc)) {
            throw new AuthException('annotation value not empty(AuthDescription): ' . $methodPath);
        }
        $features = "node@{$nodeUrl}";
        ++$this->summary['auth_meta'];
        if (isset($this->nodes[$features])) {
            $this->nodes[$features]['desc']   = $auth->desc;
            $this->nodes[$features]['policy'] = $auth->policy;
            $this->details[] = [
                'type' => 'auth_meta',
                'method' => $methodPath,
                'feature' => $features,
                'desc' => $auth->desc,
            ];
        } else {
            throw new AuthException('nodes not ready(AuthDescription): ' . $methodPath);
        }
    }

    protected function handleAuthDesc(AuthDesc $attribute): void
    {
        foreach ($attribute->desc as $node => $info) {
            if (isset($this->permissionMetaItems[$node])) {
                continue;
            }
            ++$this->summary['auth_desc'];
            if (\is_string($info)) {
                $this->permissionMetaItems[$node] = [
                    'sort' => null,
                    'desc' => $info,
                ];
            } elseif (\is_integer($info)) {
                $this->permissionMetaItems[$node] = [
                    'sort' => $info,
                    'desc' => null,
                ];
            } elseif (\is_array($info) && \is_string($info[0]) && \is_integer($info[1])) {
                $this->permissionMetaItems[$node] = [
                    'sort' => $info[1],
                    'desc' => $info[0],
                ];
            }
        }
    }

    protected function parseAuth($auth, $controllerUrl, $methodName): string
    {
        if ('self' === $auth) {
            return str_replace('/', '.', $controllerUrl) . '.' . strtolower($methodName);
        }
        return $auth;
    }

    /**
     * @return array<string, int>
     */
    private function summarizeStorage(array $output): array
    {
        $linkCount = 0;
        foreach ($output['permission2features'] ?? [] as $features) {
            $linkCount += \count($features);
        }

        return [
            'features' => \count($output['features'] ?? []),
            'permissions' => \count($output['permission'] ?? []),
            'links' => $linkCount,
        ];
    }
}
