<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration\Shovel;

class Resource1dot0 implements ResourceInterface
{
    public function __construct(
        public string $uri = '', // amqps://user:password@my-host.servicebus.windows.net:5671/?verify=verify_non
        public string $address = '', // <topic>/subscriptions/<subscription> or <topic>
        public int $prefetchCount = 1000,
        public string $autoDelete = 'never',
        public bool $addForwardingHeaders = false,
    ) {

    }

    public function getProtocol(): string
    {
        return 'amqp10';
    }

    public function toArray(): array
    {
        return [
            'protocol' => $this->getProtocol(),
            'uri' => $this->getUri(),
            'delete-after' => $this->getAutoDelete(),
            'add-forward-headers' => $this->getAddForwardingHeaders(),
            'prefetch-count' => $this->getPrefetchCount(),
            'address' => $this->address,
        ];
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAddForwardingHeaders(): bool
    {
        return $this->addForwardingHeaders;
    }

    public function getAutoDelete(): string
    {
        return $this->autoDelete;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }
}
