<?php

namespace Medeiroz\AmqpToolkit\Exceptions;

class MessageBodyUnjsonableException extends AmqpToolkitException
{
    protected $message = 'Message body is not jsonable';
}
