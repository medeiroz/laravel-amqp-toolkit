<?php

namespace Medeiroz\AmqpToolkit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Medeiroz\AmqpToolkit\AmqpToolkit
 */
class AmqpToolkit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Medeiroz\AmqpToolkit\AmqpToolkit::class;
    }
}
