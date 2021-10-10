<?php

namespace Pdik\laravelexactonline;

use Illuminate\Support\Facades\Facade;

class ExactOnlineFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ExactOnline';
    }
}