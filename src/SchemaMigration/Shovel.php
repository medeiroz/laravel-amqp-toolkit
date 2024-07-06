<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Medeiroz\AmqpToolkit\RabbitmqApi;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\WithRabbitmqApiInterface;
use Medeiroz\AmqpToolkit\SchemaMigration\Shovel\ResourceInterface;

class Shovel implements SchemaBlueprintInterface, WithRabbitmqApiInterface
{
    private ?RabbitmqApi $rabbitmqApi = null;

    public function __construct(
        public string $action,
        public string $name,
        public ?ResourceInterface $source = null,
        public ?ResourceInterface $destination = null,
        public int $reconnectDelaySeconds = 1,
        public string $acknowledgementMode = 'on-confirm', // on-confirm / on-publish / no-ack
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

    public function setRabbitmqApi(RabbitmqApi $rabbitmqApi): self
    {
        $this->rabbitmqApi = $rabbitmqApi;

        return $this;
    }

    public function getRabbitmqApi(): RabbitmqApi
    {
        return $this->rabbitmqApi;
    }

    public function runCreate(): void
    {
        if (! $this->source || ! $this->destination) {
            throw new InvalidArgumentException('Shovel source and destination must be set');
        }

        $source = $this->source->toArray();
        $source = Arr::prependKeysWith($source, 'src-');

        $destination = $this->destination->toArray();
        $destination = Arr::prependKeysWith($destination, 'dest-');

        $payload = [
            'component' => 'shovel',
            'name' => $this->name,
            'vhost' => $this->getRabbitmqApi()->connectionSettings['vhost'],
            'value' => [
                'ack-mode' => $this->acknowledgementMode,
                'reconnect-delay' => $this->reconnectDelaySeconds,
                ...$source,
                ...$destination,
            ],
        ];

        $this->getRabbitmqApi()->createShovel($this->name, $payload);
    }

    public function runCreateIfNonExists(): void
    {
        if (! $this->shovelExists($this->name)) {
            $this->runCreate();
        }
    }

    public function runDelete(): void
    {
        $this->getRabbitmqApi()->deleteShovel($this->name);
    }

    public function runDeleteIfExists(): void
    {
        if ($this->shovelExists($this->name)) {
            $this->runDelete();
        }
    }

    private function shovelExists(string $name): bool
    {
        $shovels = $this->getRabbitmqApi()->listShovels();

        return Arr::first($shovels, fn (array $shovel) => $shovel['name'] === $name) !== null;
    }
}
