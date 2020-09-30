<?php

namespace Haris\Payeer\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

class Payeer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'payeer';
    }
}