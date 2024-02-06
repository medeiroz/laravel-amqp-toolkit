<?php

namespace Medeiroz\AmqpToolkit;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Medeiroz\AmqpToolkit\Exceptions\MessageBodyUnjsonableException;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpPublisher
{
    public function __construct(
        private AmqpClient $client,
    ) {
    }

    public function onExchange(mixed $message, string $exchange, ?string $routeKey = ''): void
    {
        $this->getClient()->publish(
            message: $this->prepareBody($message),
            exchange: $exchange,
            routeKey: $routeKey,
        );
    }

    public function onQueue(mixed $message, string $queue): void
    {
        $this->getClient()->publish(
            message: $this->prepareBody($message),
            exchange: '',
            routeKey: $queue,
        );
    }

    protected function getClient(): AmqpClient
    {
        return $this->client;
    }

    protected function prepareBody(mixed $message): AMQPMessage
    {
        if ($message instanceof AMQPMessage) {
            return $message;
        }

        if ($message instanceof Arrayable) {
            $message = $message->toArray();
        }

        if ($message instanceof Jsonable) {
            $message = $message->toJson();
        } elseif ($message instanceof JsonSerializable || is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }

        if (json_decode($message) === false) {
            throw new MessageBodyUnjsonableException;
        }

        return new AMQPMessage($message);
    }
}
