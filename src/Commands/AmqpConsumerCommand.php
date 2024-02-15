<?php

namespace Medeiroz\AmqpToolkit\Commands;

use Illuminate\Console\Command;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\Events\AmqpReceivedMessageEvent;
use Medeiroz\AmqpToolkit\Exceptions\MessageBodyEmptyException;
use Medeiroz\AmqpToolkit\Exceptions\MessageBodyUnjsonableException;
use Medeiroz\AmqpToolkit\Exceptions\MessageBodyUnparsebleException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class AmqpConsumerCommand extends Command
{
    protected $signature = 'amqp:consumer {queue}';

    protected $description = 'Start a amqp consumer for a queue';

    public function __construct(protected AmqpClient $client)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        retry(
            times: (int) $this->getClient()->getSetting('max-attempts'),
            callback: fn () => $this->getClient()->consume(
                queue: $this->getQueue(),
                callback: fn (AMQPMessage $message) => $this->processCallback($message),
            ),
            sleepMilliseconds: 1000,
        );
    }

    public function processCallback(AMQPMessage $message): void
    {
        try {
            $body = $this->parseBody($message);
            $this->process($body);
            $this->accept($message);
        } catch (Throwable $exception) {
            $this->reject($message, $exception);
        }
    }

    public function getQueue(): string
    {
        return trim($this->argument('queue'));
    }

    public function process(array $body): void
    {
        $event = new AmqpReceivedMessageEvent(
            queue: $this->getQueue(),
            messageBody: $body,
        );

        event('amqp:'.$this->getQueue(), $event);
    }

    public function getClient(): AmqpClient
    {
        return $this->client;
    }

    public function accept(AMQPMessage $message): void
    {
        $this->getClient()->accept($message);
    }

    public function reject(AMQPMessage $message, Throwable $exception): void
    {
        $this->getClient()->reject($message, $exception);
    }

    public function parseBody(AMQPMessage $message): array
    {
        if (! $message->getBody()) {
            throw new MessageBodyEmptyException();
        }
        $body = json_decode(json: $message->getBody(), associative: true);
        if (! $body) {
            throw new MessageBodyUnjsonableException();
        }
        if (! is_array($body)) {
            throw new MessageBodyUnparsebleException('Inválid body format. Should be an array or object.');
        }

        return $body;
    }
}
