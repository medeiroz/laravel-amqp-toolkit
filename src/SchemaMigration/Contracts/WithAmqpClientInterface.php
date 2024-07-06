<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration\Contracts;

use Medeiroz\AmqpToolkit\AmqpClient;

interface WithAmqpClientInterface
{
    public function setAmqpClient(AmqpClient $amqpClient): self;

    public function getAmqpClient(): AmqpClient;
}
