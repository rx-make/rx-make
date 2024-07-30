<?php

declare(strict_types=1);

namespace RxMake\Facade;

use Context;
use Rhymix\Framework\Lang;

class Template
{
    public static function create(string $realAbsoluteDir, string $filename): \Rhymix\Framework\Template
    {
        $template = new \Rhymix\Framework\Template();
        $template->exists = true;

        if (!str_ends_with($filename, '.blade.php')) {
            $filename = $filename . '.blade.php';
        }
        $template->filename = $filename;
        $template->extension = 'blade.php';
        $template->absolute_dirname = realpath($realAbsoluteDir) . '/';
        $template->absolute_path = $template->absolute_dirname . $template->filename;
        $template->path = $template->absolute_dirname;

        $template->relative_dirname = self::getRelativePath(RHYMIX_DIR, $template->absolute_dirname) . '/';
        $template->relative_path = $template->relative_dirname . $template->filename;

        $cleanPath = str_replace(ROOT_DIR, '__root', $template->absolute_path);
        $template->cache_path = RHYMIX_DIR . '/files/cache/template/RxMake/' . $cleanPath . '.compiled.php';

        $template->config->version = 2;
        $template->config->autoescape = true;

        if (!$GLOBALS['lang']) {
            $GLOBALS['lang'] = Lang::getInstance(
                Context::getLangType() ?: config('locale.default_lang') ?: 'ko'
            );
        }

        $template->addVars([
            'lang' => $GLOBALS['lang'],
        ]);
        return $template;
    }

    /**
     * Get relative path of $to from $from.
     *
     * @param string $from Absolute path that is basis of the relative path.
     * @param string $to Absolute path that wants to calculate.
     *
     * @return string
     */
    private static function getRelativePath(string $from, string $to): string
    {
        $from = rtrim($from, '/');
        $to = rtrim($to, '/');

        $fromSegments = explode('/', $from);
        $toSegments = explode('/', $to);

        $relSegments = $toSegments;
        foreach ($fromSegments as $depth => $segment) {
            if ($segment === $toSegments[$depth]) {
                array_shift($relSegments);
                continue;
            }
            $remainCount = count($fromSegments) - $depth;
            if ($remainCount > 0) {
                $padLength = (count($relSegments) + $remainCount) * -1;
                $relSegments = array_pad($relSegments, $padLength, '..');
                break;
            }
            $relSegments[0] = './' . $relSegments[0];
        }

        return implode('/', $relSegments);
    }
}
