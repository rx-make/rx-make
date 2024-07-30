<?php

declare(strict_types=1);

namespace RxMake\Traits;

trait MapperConstructor
{
    public function __construct(array|object $vars = [])
    {
        foreach ($vars as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
