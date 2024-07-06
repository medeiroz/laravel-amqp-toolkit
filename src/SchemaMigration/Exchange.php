<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use InvalidArgumentException;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\Enums\ExchangesTypes;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\WithAmqpClientInterface;

class Exchange implements SchemaBlueprintInterface, WithAmqpClientInterface
{
    private ?AmqpClient $amqpClient = null;

    public function __construct(
        public string $action,
        public string $name,
        public ExchangesTypes $type = ExchangesTypes::FANOUT,
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

    public function runCreate(): void
    {
        $this->getAmqpClient()->createExchange($this->name, $this->type);
    }

    public function runCreateIfNonExists(): void
    {
        if (! $this->getAmqpClient()->exchangeExists($this->name)) {
            $this->runCreate();
        }
    }

    public function runDelete(): void
    {
        $this->getAmqpClient()->deleteExchange($this->name);
    }

    public function runDeleteIfExists(): void
    {
        if ($this->getAmqpClient()->exchangeExists($this->name)) {
            $this->runDelete();
        }
    }
}
