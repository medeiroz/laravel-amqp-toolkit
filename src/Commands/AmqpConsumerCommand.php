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
        $this->components->info(sprintf('Starting consume queue %s ...', $this->getQueue()));

        try {
            retry(
                times: (int) $this->getClient()->getSetting('max-attempts'),
                callback: fn () => $this->getClient()->consume(
                    queue: $this->getQueue(),
                    callback: fn (AMQPMessage $message) => $this->processCallback($message),
                ),
                sleepMilliseconds: function (int $attempts) {
                    $this->components->warn(sprintf('Consumer failed. Retrying attempt %u ...', $attempts));

                    return $attempts * 500;
                },
            );
        } catch (Throwable $exception) {
            $this->components->error('Consumer failed.');
            throw $exception;
        }

        $this->components->info('Consumer finished.');
    }

    public function processCallback(AMQPMessage $message): void
    {
        try {
            $body = $this->parseBody($message);
            $this->process($body);
            $this->accept($message);
            $this->components->info(
                sprintf(
                    'Queue: %s | MessageID: %u | %s',
                    $this->getQueue(),
                    $message->getDeliveryTag(),
                    'Message accepted.',
                )
            );
        } catch (Throwable $exception) {
            $this->reject($message, $exception);
            $this->components->error(
                sprintf(
                    'Queue: %s | MessageID: %u | %s | Exception: %s',
                    $this->getQueue(),
                    $message->getDeliveryTag(),
                    'Message rejected.',
                    $exception->getMessage(),
                )
            );
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
