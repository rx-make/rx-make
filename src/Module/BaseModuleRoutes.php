<?php

declare(strict_types=1);

namespace RxMake\Module;

use BaseObject;
use Context;
use FastRoute\ConfigureRoutes;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\Result\Matched;
use FastRoute\FastRoute;
use ModuleObject;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Exceptions\SecurityViolation;
use Rhymix\Framework\Exceptions\TargetNotFound;
use Rhymix\Framework\Security;
use RuntimeException;

abstract class BaseModuleRoutes extends BaseModule
{
    private bool $isAdminRoutes;
    private string $routeActName;

    public function proc(): bool
    {
        $this->isAdminRoutes = str_ends_with(static::class, 'AdminRoutes');
        $this->routeActName = Context::get('act')
            ?: $this->xml_info->{$this->isAdminRoutes ? 'admin_index_act' : 'default_index_act'};
        if ($this->act === $this->routeActName) {
            /** @var $act BaseModuleRoutes::handleInternal(...) */
            $this->act = 'handleInternal';
            $this->xml_info->action->handleInternal = $this->xml_info->action->{$this->routeActName};
        }
        return parent::proc();
    }

    /**
     * @throws InvalidRequest
     * @throws TargetNotFound
     * @throws SecurityViolation
     */
    public function handleInternal(): BaseObject
    {
        if (!$this->routeActName) {
            throw new RuntimeException('Cannot call handleInternal() directly');
        }
        $this->act = $this->routeActName;
        $cacheDir = RHYMIX_DIR . '/files/cache/RxMake/Foundation/Modules/BaseModuleRoutes';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $fastRoute = FastRoute::recommendedSettings(
            $this->routes(...),
            cacheKey: $cacheDir . '/' . $this->act
        );
        if (($_ENV['APP_ENV'] ?? '') === 'develop') {
            $fastRoute = $fastRoute->disableCache();
        }

        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            Context::setResponseMethod('JSON');
        }
        $routesInfo = $fastRoute->dispatcher()->dispatch(
            httpMethod: $httpMethod = $_SERVER['REQUEST_METHOD'],
            uri: $this->getModuleScopedRequestUri($httpMethod)
        );

        switch ($routesInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new TargetNotFound();
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new InvalidRequest('');
            case Dispatcher::FOUND:
                $routesInfo = $this->handleRouteOptions($routesInfo, $httpMethod);
        }

        $vars = $routesInfo[2];
        foreach ($vars as $key => $value) {
            Context::set($key, $value);
        }

        $handler = $routesInfo[1];
        if (is_callable($handler)) {
            $output = $handler($vars);
            if (!($output instanceof BaseObject)) {
                if ($output === null) {
                    $output = new BaseObject();
                }
                else if (is_bool($output)) {
                    $output = new BaseObject($output ? 0 : -1, $output ? 'success' : 'error');
                }
                else if (is_string($output)) {
                    $output = new BaseObject(0, $output);
                }
                else if (is_array($output) || is_object($output)) {
                    $output = new BaseObject();
                    $output->sets($output);
                }
                else {
                    throw new RuntimeException('Unexpected return type');
                }
            }
        }
        else {
            if (is_string($handler) && str_contains($handler, '@')) {
                $handler = explode('@', $handler, 2);
            }
            if (count($handler) !== 2) {
                throw new RuntimeException('Unknown route handler');
            }
            if (!class_exists($handler[0]) || !method_exists($handler[0], $handler[1])) {
                throw new RuntimeException('Unknown route handler');
            }
            if (!is_subclass_of($handler[0], ModuleObject::class)) {
                throw new RuntimeException('Unknown route handler');
            }
            $output = $handler[0]::getInstance();
            foreach ($this as $key => $value) {
                $output->{$key} = $value;
            }
            $output->{$handler[1]}();
        }

        foreach ($output as $key => $value) {
            $this->{$key} = $value;
        }
        return $output;
    }

    private function getModuleScopedRequestUri(string $httpMethod): string
    {
        if ($this->isAdminRoutes) {
            return '/' . trim(Context::get('route') ?? '', '/');
        }
        if ($httpMethod === 'POST' || $httpMethod === 'PUT' || $httpMethod === 'PATCH') {
            return RXMAKE_ROUTE ?? '/';
        }
        $segments = explode(
            separator: '/',
            string: $this->request->url,
            limit: $limit = str_starts_with($this->request->url, $this->request->mid . '/') ? 3 : 2,
        );
        return '/' . ($segments[$limit - 1] ?? '');
    }

    /**
     * @param Matched $routesInfo
     * @param string  $httpMethod
     *
     * @return Matched
     * @throws SecurityViolation
     */
    private function handleRouteOptions(Matched $routesInfo, string $httpMethod): Matched
    {
        $options = $routesInfo->extraParameters;
        if (!in_array(RouteOptions::NoCsrfCheck, $options)) {
            if (
                ($httpMethod === 'POST' || $httpMethod === 'PUT' || $httpMethod === 'PATCH')
                && !Security::checkCSRF()
            ) {
                throw new SecurityViolation('ERR_CSRF_CHECK_FAILED');
            }
        }
        return $routesInfo;
    }

    abstract function routes(ConfigureRoutes $r): void;
}
