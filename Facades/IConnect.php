<?php

namespace Adietz\HSPIConnect\Facades;

use Illuminate\Support\Facades\Facade;

class IConnect extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'iconnect';
    }

}