<?php

namespace Medeiroz\AmqpToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\Events\AmqpReceivedMessageEvent;
use Medeiroz\AmqpToolkit\Exceptions\MessageBodyEmptyException;
use Medeiroz\AmqpToolkit\Exceptions\MessageBodyUnjsonableException;
use Medeiroz\AmqpToolkit\Exceptions\MessageBodyUnparsebleException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class AmqpConsumerCommand extends Command
{
    protected $signature = 'amqp:consumer {queue?*}';

    protected $description = 'Start a amqp consumer for a queues list
        {queue: The queues to be consumed}
        Accept a list of queues to be consumed.
        When no queue is provided, the command will consume all queues configured in the consumer-queues key in the amqp-toolkit configuration file.
    ';

    public function __construct(private readonly AmqpClient $client)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->validateHasQueuesToConsume();
        $this->checkQueuesExists();
        $this->analyseQueuesHasListeners();
        $this->startConsumption();
    }

    public function startConsumption(): void
    {
        $this->components->info(
            sprintf(
                'Starting consume %s %s ...',
                $this->getQueuesLabel(),
                implode(', ', $this->getQueueNames()),
            )
        );

        try {
            $this->consumeQueues();
        } catch (Throwable $exception) {
            $this->components->error("Consumer failed. {$exception->getMessage()}");
            throw $exception;
        }
        $this->components->info('Consumer finished.');
    }

    private function consumeQueues(): void
    {
        retry(
            times: $this->getMaxAttempts(),
            callback: fn () => $this->client->consume(
                queue: $this->getQueueNames(),
                callback: fn (string $queue, AMQPMessage $message) => $this->processCallback($queue, $message),
            ),
            sleepMilliseconds: $this->retrySleepMilliseconds(),
        );
    }

    private function retrySleepMilliseconds(): callable
    {
        return function (int $attempts, Throwable $exception) {
            $this->components->warn(sprintf(
                'Consumer failed. Retrying attempt %u. Exception: %s ...',
                $attempts,
                $exception->getMessage()
            ));

            return $attempts * 500;
        };
    }

    private function getMaxAttempts(): int
    {
        return (int) config('amqp-toolkit.max-attempts', 10);
    }

    private function getQueues(): array
    {
        return config('amqp-toolkit.consumer-queues', []);
    }

    private function getQueueNames(): array
    {
        return $this->argument('queue')
            ?: array_keys($this->getQueues());
    }

    public function getQueuesLabel(): string
    {
        return Str::plural('queue', count($this->getQueueNames()));
    }

    private function validateHasQueuesToConsume(): void
    {
        if (count($this->getQueueNames()) < 1) {
            $this->components->error('No queues to consume.');
            exit(1);
        }
    }

    private function checkQueuesExists(): void
    {
        $queueNonExists = array_filter(
            $this->getQueueNames(),
            fn (string $queue) => ! $this->client->queueExists($queue),
        );

        if (count($queueNonExists) > 0) {
            $queuesLabel = Str::plural('Queue', count($queueNonExists));
            $this->components->error(sprintf(
                '%s [%s] not exists in AMQP server. Try run `php artisan amqp:migrate` to migrate queues',
                $queuesLabel,
                implode(', ', $queueNonExists),

            ));
            exit(1);
        }
    }

    private function analyseQueuesHasListeners(): void
    {
        array_map(function (string $queue) {
            if (! Event::hasListeners("amqp:$queue")) {
                $this->components->warn("Queue $queue has no listeners.");
            }
        }, $this->getQueueNames());
    }

    private function processCallback(string $queue, AMQPMessage $message): void
    {
        try {
            $body = $this->parseBody($message);
            $this->process($queue, $body);
            $this->accept($queue, $message);

        } catch (Throwable $exception) {
            $this->reject($queue, $message, $exception);
            report($exception);
        }
    }

    private function process(string $queue, array $body): void
    {
        $event = new AmqpReceivedMessageEvent(queue: $queue, messageBody: $body);

        event("amqp:$queue", $event);
    }

    private function accept(string $queue, AMQPMessage $message): void
    {
        $this->components->info(
            sprintf(
                'Queue: %s | MessageID: %u | %s',
                $queue,
                $message->getDeliveryTag(),
                'Message accepted.',
            )
        );

        $this->client->accept($message);
    }

    private function reject(string $queue, AMQPMessage $message, Throwable $exception): void
    {
        $this->components->error(
            sprintf(
                'Queue: %s | MessageID: %u | %s | Exception: %s',
                $queue,
                $message->getDeliveryTag(),
                'Message rejected.',
                $exception->getMessage(),
            )
        );

        $this->client->reject($message, $exception);
    }

    private function parseBody(AMQPMessage $message): array
    {
        if (! $message->getBody()) {
            throw new MessageBodyEmptyException();
        }
        $body = json_decode(json: $message->getBody(), associative: true);
        if (! $body) {
            throw new MessageBodyUnjsonableException();
        }
        if (! is_array($body)) {
            throw new MessageBodyUnparsebleException('Invalid body format. Should be an array or object.');
        }

        return $body;
    }
}
