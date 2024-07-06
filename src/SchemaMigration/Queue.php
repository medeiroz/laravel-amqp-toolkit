<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use InvalidArgumentException;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\WithAmqpClientInterface;

class Queue implements SchemaBlueprintInterface, WithAmqpClientInterface
{
    private ?AmqpClient $amqpClient = null;

    public function __construct(
        public string $action,
        public string $name,
        public bool $retry = false,
        public int $ttl = 60000, // 60 seconds
        public bool $dlq = false,
        public ?string $exchange = null,
        public ?string $routeKey = null,
    ) {}

    public function run(): void
    {
        match ($this->action) {
            'create' => $this->runCreate(),
            'create-if-non-exists' => $this->runCreateIfNonExists(),
            'delete' => $this->runDelete(),
            'delete-if-exists' => $this->runDeleteIfExists(),
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

    public function withRetry(bool $retry = true): self
    {
        $this->retry = $retry;

        return $this;
    }

    public function withTtl(int $seconds = 10): self
    {
        $this->ttl = ($seconds * 1000);

        return $this;
    }

    public function withDlq(bool $withDlq = true): self
    {
        $this->dlq = $withDlq;

        return $this;
    }

    public function bind(string $exchange, ?string $routeKey = null): self
    {
        $this->exchange = $exchange;
        $this->routeKey = $routeKey;

        return $this;
    }

    public function runCreate(): void
    {
        if ($this->retry) {
            $arguments = [
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $this->name,
                'x-message-ttl' => $this->ttl,
            ];
            $this->getAmqpClient()->createQueue("$this->name.retry", $arguments);
        }

        if ($this->dlq) {
            $this->getAmqpClient()->createQueue("$this->name.dlq");
        }

        $arguments = ($this->retry)
            ? [
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => "$this->name.retry",
            ]
            : [];

        $this->getAmqpClient()->createQueue($this->name, $arguments);

        if ($this->exchange) {
            $this->getAmqpClient()->bind($this->name, $this->exchange, $this->routeKey ?: '');
        }
    }

    public function runCreateIfNonExists(): void
    {
        if (! $this->getAmqpClient()->queueExists($this->name)) {
            $this->runCreate();
        }
    }

    public function runDelete(): void
    {
        if ($this->retry) {
            $this->getAmqpClient()->deleteQueue("$this->name.retry");
        }

        if ($this->dlq) {
            $this->getAmqpClient()->deleteQueue("$this->name.dlq");
        }

        $this->getAmqpClient()->deleteQueue($this->name);
    }

    public function runDeleteIfExists(): void
    {
        if ($this->getAmqpClient()->queueExists($this->name)) {
            $this->runDelete();
        }
    }
}
