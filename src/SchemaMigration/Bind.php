<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use InvalidArgumentException;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;

class Bind implements SchemaBlueprintInterface
{
    public function __construct(
        public string $action,
        public string $queue,
        public string $exchange,
        public string $routingKey = '',
    ) {}

    public function run(AmqpClient $client): void
    {
        match ($this->action) {
            'bind' => $client->bind($this->queue, $this->exchange, $this->routingKey),
            'unbind' => $client->unbind($this->queue, $this->exchange, $this->routingKey),
            default => throw new InvalidArgumentException("Invalid action: {$this->action}"),
        };
    }
}
