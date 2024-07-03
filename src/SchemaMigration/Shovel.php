<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;
use Medeiroz\AmqpToolkit\SchemaMigration\Shovel\ResourceInterface;

class Shovel implements SchemaBlueprintInterface
{
    public function __construct(
        public string $action,
        public string $name,
        public ?ResourceInterface $source = null,
        public ?ResourceInterface $destination = null,
        public int $reconnectDelaySeconds = 1,
        public string $acknowledgementMode = 'on-confirm', // on-confirm / on-publish / no-ack
    ) {}

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
        $source = $this->source->toArray();
        $source = Arr::prependKeysWith($source, 'src-');

        $destination = $this->destination->toArray();
        $destination = Arr::prependKeysWith($destination, 'dest-');

        $payload = [
            'component' => 'shovel',
            'name' => $this->name,
            'vhost' => $client->getSetting('vhost'),
            'value' => [
                'ack-mode' => $this->acknowledgementMode,
                'reconnect-delay' => $this->reconnectDelaySeconds,
                ...$source,
                ...$destination,
            ],
        ];

        $payload = $this->filterAllowedProperties($payload);

        $baseUrl = $client->getSetting('host').':'.$client->getSetting('api-port');

        $response = Http::withBasicAuth($client->getSetting('user'), $client->getSetting('password'))
            ->put("$baseUrl/api/parameters/shovel/%2F/{$this->name}", $payload);

        if ($response->json('error')) {
            throw new Exception("Error creating shovel {$this->name}: {$response->json('error')} : {$response->json('reason')}");
        }
    }

    public function runDelete(AmqpClient $client): void
    {
        $baseUrl = $client->getSetting('host').':'.$client->getSetting('api-port');

        $response = Http::withBasicAuth($client->getSetting('user'), $client->getSetting('password'))
            ->delete("$baseUrl/api/parameters/shovel/%2F/{$this->name}");

        if ($response->json('error')) {
            throw new Exception("Error creating shovel {$this->name}: {$response->json('error')} : {$response->json('reason')}");
        }
    }

    private function filterAllowedProperties(array $payload): array
    {
        $whiteList = [
            'component',
            'vhost',
            'name',
            'value' => [
                'ack-mode',
                'reconnect-delay',
                'src-uri',
                'src-protocol',
                'src-queue',
                'src-prefetch-count',
                'src-delete-after',
                'src-address',
                'src-exchange',
                'src-exchange-key',
                'dest-uri',
                'dest-protocol',
                'dest-queue',
                'dest-add-forward-headers',
                'dest-address',
                'dest-exchange',
                'dest-exchange-key',
            ],
        ];

        $payload['value'] = Arr::only($payload['value'], $whiteList['value']);

        return $payload;
    }
}
