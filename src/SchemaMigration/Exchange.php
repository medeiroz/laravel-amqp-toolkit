<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use InvalidArgumentException;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\Enums\ExchangesTypes;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;

class Exchange implements SchemaBlueprintInterface
{
    public function __construct(
        public string $action,
        public string $name,
        public ExchangesTypes $type = ExchangesTypes::FANOUT,
    ) {
    }

    public function run(AmqpClient $client): void
    {
        match ($this->action) {
            'create' => $client->createExchange($this->name, $this->type),
            'delete' => $client->deleteExchange($this->name),
            default => throw new InvalidArgumentException("Invalid action: {$this->action}"),
        };
    }
}
