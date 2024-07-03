<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use InvalidArgumentException;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;

class Queue implements SchemaBlueprintInterface
{
    public function __construct(
        public string $action,
        public string $name,
        public bool $retry = false,
        public int $ttl = 60000, // 60 seconds
        public bool $dlq = false,
        public ?string $exchange = null,
    ) {}

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

    public function bind(string $exchange): self
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function run(AmqpClient $client): void
    {
        match ($this->action) {
            'create' => $this->runCreate($client),
            'delete' => $this->runDelete($client),
            default => throw new InvalidArgumentException("Invalid action: {$this->action}"),
        };
    }

    public function runCreate(AmqpClient $client): void
    {
        if ($this->retry) {
            $arguments = [
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $this->name,
                'x-message-ttl' => $this->ttl,
            ];
            $client->createQueue($this->name.'.retry', $arguments);
        }

        if ($this->dlq) {
            $client->createQueue($this->name.'.dlq');
        }

        $arguments = ($this->retry)
            ? [
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $this->name.'.retry',
            ]
            : [];

        $client->createQueue($this->name, $arguments);

        if ($this->exchange) {
            $client->bind($this->name, $this->exchange);
        }
    }

    public function runDelete(AmqpClient $client): void
    {
        if ($this->retry) {
            $client->deleteQueue($this->name.'.retry');
        }

        if ($this->dlq) {
            $client->deleteQueue($this->name.'.dlq');
        }

        $client->deleteQueue($this->name);
    }
}
