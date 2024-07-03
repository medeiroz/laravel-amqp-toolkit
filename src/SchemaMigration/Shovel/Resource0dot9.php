<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration\Shovel;

class Resource0dot9 implements ResourceInterface
{
    public function __construct(
        public string $type = 'queue', // queue or exchange
        public string $uri = 'amqp://',
        public string $queue = '',
        public string $exchange = '',
        public string $exchangeKey = '',
        public int $prefetchCount = 1000,
        public string $autoDelete = 'never',
        public bool $addForwardingHeaders = false,
    ) {}

    public function getProtocol(): string
    {
        return 'amqp091';
    }

    public function toArray(): array
    {
        $array = [
            'protocol' => $this->getProtocol(),
            'uri' => $this->getUri(),
            'delete-after' => $this->getAutoDelete(),
            'add-forward-headers' => $this->getAddForwardingHeaders(),
            'prefetch-count' => $this->getPrefetchCount(),
        ];

        if ($this->type === 'queue') {
            $array['queue'] = $this->queue;
        } else {
            $array['exchange'] = $this->exchange;
            $array['exchange-key'] = $this->exchangeKey;
        }

        return $array;
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
