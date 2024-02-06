<?php

namespace Medeiroz\AmqpToolkit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Medeiroz\AmqpToolkit\AmqpPublisher
 */
class AmqpPublisher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Medeiroz\AmqpToolkit\AmqpPublisher::class;
    }
}
