<?php

namespace Zxin\Think\Route;

use think\App;
use Zxin\Think\Annotation\Core\Scanning;
use Zxin\Think\Route\Annotation\Group as GroupAttr;
use Zxin\Think\Route\Annotation\Middleware as MiddlewareAttr;
use Zxin\Think\Route\Annotation\Resource as ResourceAttr;
use Zxin\Think\Route\Annotation\ResourceRule as ResourceRuleAttr;
use Zxin\Think\Route\Annotation\Route as RouteAttr;

class RouteScanning
{
    private string $controllerLayer;
    /**
     * @var array<string, int>
     */
    private array $summary = [];
    /**
     * @var list<array<string, scalar|null>>
     */
    private array $details = [];

    public function __construct(private App $app)
    {
        $this->controllerLayer = $this->app->route->config('controller_layer') ?: 'controller';
    }

    public function classToRouteName(string $class): string
    {
        $appNamespace = $this->app->getNamespace();

        $controllerName = str_replace("{$appNamespace}\\{$this->controllerLayer}\\", '', $class);
        return str_replace('\\', '.', $controllerName);
    }

    private function dataProvider(): \Generator
    {
        $appNamespace = $this->app->getNamespace();
        $scanning = new Scanning($this->app, "{$appNamespace}\\");

        foreach ($scanning->scanningClass() as $file => $class) {
            try {
                $refClass = new \ReflectionClass($class);
            } catch (\ReflectionException) {
                continue;
            }
            if ($refClass->isAbstract() || $refClass->isTrait()) {
                continue;
            }

            yield [$file, $class, $refClass];
        }
    }

    public function scan(): array
    {
        return $this->scanWithReport()['data'];
    }

    /**
     * @return array{data: array, summary: array<string, int>, details: list<array<string, scalar|null>>}
     */
    public function scanWithReport(): array
    {
        $cutPathLen = \strlen($this->app->getRootPath());

        $items = [];

        $refMap = [];
        $seenClasses = [];
        $this->summary = [
            'controllers' => 0,
            'groups' => 0,
            'resources' => 0,
            'resource_rules' => 0,
            'route_methods' => 0,
            'routes' => 0,
            'middlewares' => 0,
        ];
        $this->details = [];

        foreach ($this->dataProvider() as [$file, $class, $refClass]) {
            /**
             * @var \ReflectionClass $refClass
             */
            /** @var GroupAttr|null $groupAttr */
            $groupAttr = ($refClass->getAttributes(GroupAttr::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)?->newInstance();
            $seenClasses[$class] = true;

            $sort = $groupAttr !== null ? $groupAttr->registerSort : 1000;
            if ($groupAttr !== null) {
                ++$this->summary['groups'];
            }

            $refMap[$class] = $refClass;

            /** @var MiddlewareAttr[] $middlewareAttr */
            $middlewareAttr = array_map(
                fn ($attr) => $attr->newInstance(),
                $refClass->getAttributes(MiddlewareAttr::class, \ReflectionAttribute::IS_INSTANCEOF)
            );
            $this->summary['middlewares'] += \count($middlewareAttr);

            $filename = (string) $file;
            $filename = substr($filename, $cutPathLen);

            $resourceArr = $refClass->getAttributes(ResourceAttr::class, \ReflectionAttribute::IS_INSTANCEOF);
            if ($resourceArr) {
                foreach ($resourceArr as $resourceAttr) {
                    $resourceAttrInst = $resourceAttr->newInstance();
                    ++$this->summary['resources'];
                    $items[] = [
                        'file'          => $filename,
                        'class'         => $class,
                        'controller'    => $this->classToRouteName($class),
                        'sort'          => $resourceAttrInst->registerSort ?? $sort,
                        'group'         => $groupAttr,
                        'middleware'    => $middlewareAttr,
                        'resource'      => $resourceAttrInst,
                        'resourceItems' => [],
                        'routeItems'    => [],
                    ];
                    $this->details[] = [
                        'type' => 'resource',
                        'class' => $class,
                        'file' => $filename,
                        'name' => $resourceAttrInst->name,
                    ];
                }
            } else {
                $items[] = [
                    'file'          => $filename,
                    'class'         => $class,
                    'controller'    => $this->classToRouteName($class),
                    'sort'          => $sort,
                    'group'         => $groupAttr,
                    'middleware'    => $middlewareAttr,
                    'resource'      => null,
                    'resourceItems' => [],
                    'routeItems'    => [],
                ];
            }
        }
        $this->summary['controllers'] = \count($seenClasses);

        usort($items, fn ($a, $b) => $b['sort'] <=> $a['sort']);


        foreach ($items as &$item) {
            $classRef = $refMap[$item['class']];
            $this->parseMethod($item, $classRef);
        }

        return [
            'data' => $items,
            'summary' => $this->summary,
            'details' => $this->details,
        ];
    }

    public function parseMethod(array &$groupItem, \ReflectionClass $refClass): void
    {
        // todo 支持排序

        // 资源路由
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $refMethod) {
            $methodName = $refMethod->getName();

            if (!$refMethod->isPublic() || $refMethod->isStatic()) {
                continue;
            }
            if (str_starts_with($methodName, '_')) {
                continue;
            }

            $attr = $refMethod->getAttributes(ResourceRuleAttr::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
            /** @var ResourceRuleAttr|null $rrule */
            $rrule = $attr?->newInstance();

            if ($rrule !== null) {
                $groupItem['resourceItems'][] = [
                    'method' => $methodName,
                    'attr'   => $rrule,
                ];
                ++$this->summary['resource_rules'];
                $this->details[] = [
                    'type' => 'resource_rule',
                    'class' => $groupItem['class'],
                    'method' => $methodName,
                    'name' => $rrule->name ?: $methodName,
                    'http_method' => $rrule->method,
                ];
            }

            /** @var RouteAttr[] $route */
            $route = array_map(
                fn ($attr) => $attr->newInstance(),
                $refMethod->getAttributes(RouteAttr::class, \ReflectionAttribute::IS_INSTANCEOF),
            );

            if ($route !== []) {
                /** @var MiddlewareAttr[] $middleware */
                $middleware = array_map(
                    fn ($attr) => $attr->newInstance(),
                    $refMethod->getAttributes(MiddlewareAttr::class, \ReflectionAttribute::IS_INSTANCEOF),
                );
                $this->summary['middlewares'] += \count($middleware);

                usort($route, fn ($a, $b) => $b->registerSort <=> $a->registerSort);

                $groupItem['routeItems'][] = [
                    'method'     => $refMethod->getName(),
                    'route'      => $route,
                    'middleware' => $middleware,
                ];
                ++$this->summary['route_methods'];
                $this->summary['routes'] += \count($route);
                foreach ($route as $routeAttr) {
                    $this->details[] = [
                        'type' => 'route',
                        'class' => $groupItem['class'],
                        'method' => $methodName,
                        'name' => $routeAttr->name,
                        'http_method' => $routeAttr->method,
                    ];
                }
            }
        }

        $routeItems = &$groupItem['routeItems'];

        usort($routeItems, fn ($a, $b) => $b['route'][0]->registerSort <=> $a['route'][0]->registerSort);
    }
}
