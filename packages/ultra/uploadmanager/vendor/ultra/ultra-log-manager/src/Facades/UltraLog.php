<?php


namespace Ultra\UltraLogManager\Facades;

use Illuminate\Support\Facades\Facade;

class UltraLog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ultralogmanager';
    }
}
