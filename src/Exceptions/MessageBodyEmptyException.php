<?php

namespace Medeiroz\AmqpToolkit\Exceptions;

class MessageBodyEmptyException extends AmqpToolkitException
{
    protected $message = 'Message body is empty';
}
