<?php

namespace SmartLInks\LaravelKnet\Facades;

use Illuminate\Support\Facades\Facade;

class Knet extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'knet';
    }
}