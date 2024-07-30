<?php

declare(strict_types=1);

namespace RxMake\Module;

use Context;
use ModuleObject;
use RuntimeException;

class BaseModule extends ModuleObject
{
    /**
     * Get module name.
     *
     * @return string
     */
    public function getModuleName(): string
    {
        if ($this->module) {
            return $this->module;
        }

        $namespaceFound = false;
        foreach ($this->xml_info->namespaces ?? [] as $namespace) {
            if (str_starts_with(static::class, $namespace)) {
                $namespaceFound = true;
                break;
            }
        }
        if (!$namespaceFound) {
            throw new RuntimeException('Cannot determine module name');
        }

        foreach ($this->xml_info->action ?? [] as $action => $_) {
            if (str_ends_with($action, 'HandleRequest')) {
                if (!preg_match(
                    pattern: '/(?:disp|proc)([A-Z][a-z_]+)(?:Admin)?HandleRequest/',
                    subject: $action,
                    matches: $output
                )) {
                    continue;
                }
                $moduleName = $output[1];
                if ($moduleName) {
                    return $this->module = $moduleName;
                }
            }
        }

        throw new RuntimeException('Cannot determine module name');
    }

    /**
     * Set a template path relative on the module path.
     *
     * @param string $path Relative path.
     *
     * @return self
     */
    public function setRelativeTemplatePath(string $path): self
    {
        $path = './modules/' . $this->getModuleName() . '/' . $path;
        return $this->setTemplatePath($path);
    }

    /**
     * Set redirect URL by route.
     *
     * @param string $route
     *
     * @return self
     */
    public function setRedirectRoute(string $route): self
    {
        $route = '/' . trim($route, '/');
        if ($this->module_info->module === 'admin') {
            return $this->setRedirectUrl(getNotEncodedFullUrl(
                '',
                'module', 'admin',
                'act', 'disp' . ucfirst($this->getModuleName()) . 'AdminHandleRequest',
                'route', $route,
            ));
        }
        return $this->setRedirectUrl($route);
    }

    /**
     * Set validator message that will be used as flash message.
     *
     * @param 'info'|'warning'|'error' $type
     * @param string $message
     *
     * @return self
     */
    public function setValidatorMessage(string $type, string $message): self
    {
        Context::setValidatorMessage(
            static::class,
            $message,
            $type,
        );
        return $this;
    }
}
