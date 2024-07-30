<?php

declare(strict_types=1);

namespace RxMake\Module\Admin;

use Context;
use RxMake\Facade\Template;
use Throwable;

class AdminComponent
{
    /**
     * Load admin component and compile into string.
     *
     * @param string       $component Specific admin component name.
     * @param array|object $vars      Variables to pass.
     *
     * @return string
     */
    public static function load(string $component, array|object $vars = []): string
    {
        $template = Template::create(__DIR__ . '/Views', $component);
        $template->addVars($vars);
        try {
            return $template->compile();
        } catch (Throwable $e) {
            return Context::get('logged_info')->is_admin === 'Y'
                ? 'Error: ' . $e->getMessage() . ' with trace: ' . $e->getTraceAsString()
                : 'Something went wrong';
        }
    }

    /**
     * Print out the header component.
     *
     * @param string      $title       Title.
     * @param string|null $description Description that will be printed out if the help icon has been clicked.
     *
     * @return string
     */
    public static function header(string $title, string|null $description = null): string
    {
        echo self::load('Header', [
            'title' => $title,
            'description' => $description,
        ]);
        return '';
    }

    /**
     * Print out the header component with path information.
     *
     * @param string                $title Title.
     * @param array<string, string> $path Path name and URL mapped array. key as name, value as URL.
     *
     * @return string
     */
    public static function headerWithPath(string $title, array $path = []): string
    {
        echo self::load('HeaderWithPath', [
            'title' => $title,
            'path' => $path,
        ]);
        return '';
    }

    /**
     * Print out the form injection component.
     *
     * @param string $route Route to submit.
     *
     * @return string
     */
    public static function formInjection(string $route): string
    {
        echo self::load('FormInjection', [
            'route' => $route,
        ]);
        return '';
    }

    /**
     * Print out the form submit component.
     *
     * @return string
     */
    public static function formSubmit(): string
    {
        echo self::load('FormSubmit');
        return '';
    }
}
