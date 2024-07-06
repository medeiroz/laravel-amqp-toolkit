<?php

namespace Medeiroz\AmqpToolkit;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;

class RabbitmqApi
{
    public function __construct(public array $connectionSettings) {}

    protected function request(): PendingRequest
    {
        return Http::baseUrl($this->connectionSettings['host'].':'.$this->connectionSettings['api-port'])
            ->withBasicAuth($this->connectionSettings['user'], $this->connectionSettings['password'])
            ->throw()
            ->withResponseMiddleware(function (ResponseInterface $response) {
                $body = json_decode($response->getBody()->getContents(), true);

                if ($body['error'] ?? false) {
                    throw new Exception('Error rabbitmq api: '.json_encode($body));
                }

                return $response;
            });
    }

    public function listShovels(): array
    {
        $vhost = urlencode($this->connectionSettings['vhost']);

        return $this->request()
            ->get("/api/shovels/$vhost")
            ->json();
    }

    public function createShovel(string $name, array $payload): ?array
    {
        $payload = $this->filterAllowedProperties($payload);

        $vhost = $payload['vhost'] ?? $this->connectionSettings['vhost'];
        $vhost = urlencode($vhost);

        return $this->request()
            ->put("api/parameters/shovel/$vhost/$name", $payload)
            ->json();
    }

    public function deleteShovel(string $name): ?array
    {
        $vhost = urlencode($this->connectionSettings['vhost']);

        return $this->request()
            ->delete("api/parameters/shovel/$vhost/$name")
            ->json();
    }

    protected function filterAllowedProperties(array $payload): array
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
