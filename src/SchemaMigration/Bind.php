<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use InvalidArgumentException;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\WithAmqpClientInterface;

class Bind implements SchemaBlueprintInterface, WithAmqpClientInterface
{
    private ?AmqpClient $amqpClient = null;

    public function __construct(
        public string $action,
        public string $queue,
        public string $exchange,
        public string $routingKey = '',
    ) {}

    public function run(): void
    {
        match ($this->action) {
            'bind' => $this->runBind(),
            'bind-if-exists' => $this->runBindIfExists(),
            'unbind' => $this->runUnbind(),
            'unbind-if-exists' => $this->runUnbindIfExists(),
            default => throw new InvalidArgumentException("Invalid action: $this->action"),
        };
    }

    public function setAmqpClient(AmqpClient $amqpClient): self
    {
        $this->amqpClient = $amqpClient;

        return $this;
    }

    public function getAmqpClient(): AmqpClient
    {
        return $this->amqpClient;
    }

    public function runBind(): void
    {
        $this->getAmqpClient()->bind($this->queue, $this->exchange, $this->routingKey ?: '');
    }

    public function runBindIfExists(): void
    {
        if ($this->getAmqpClient()->queueExists($this->queue) && $this->getAmqpClient()->exchangeExists($this->exchange)) {
            $this->runBind();
        }
    }

    public function runUnbind(): void
    {
        $this->getAmqpClient()->unbind($this->queue, $this->exchange, $this->routingKey ?: '');
    }

    public function runUnbindIfExists(): void
    {
        if ($this->getAmqpClient()->queueExists($this->queue) && $this->getAmqpClient()->exchangeExists($this->exchange)) {
            $this->runUnbind();
        }
    }
}
