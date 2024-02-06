<?php

namespace Medeiroz\AmqpToolkit\Exceptions;

class MessageBodyUnparsebleException extends AmqpToolkitException
{
    protected $message = 'Message body is not parseble';
}
