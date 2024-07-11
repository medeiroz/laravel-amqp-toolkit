<?php

namespace Medeiroz\AmqpToolkit;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Medeiroz\AmqpToolkit\Entities\QueueInfo;
use Medeiroz\AmqpToolkit\Enums\ExchangesTypes;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Throwable;

class AmqpClient
{
    protected ?AbstractConnection $connection = null;

    protected ?AMQPChannel $channel = null;

    public function __construct(
        protected AMQPConnectionFactory $amqpConnectionFactory,
        protected LoggerInterface $logger,
        protected array $settings,
    ) {}

    public function __destruct()
    {
        $this->disconnect();
    }

    public function connect(): void
    {
        if ($this->connection && $this->connection->isConnected() && $this->channel && $this->channel->is_open()) {
            return;
        }

        $this->logger->debug('Connecting AMQP server...');

        $config = new AMQPConnectionConfig;
        $config->setHost($this->getSetting('host'));
        $config->setPort((int) $this->getSetting('port'));
        $config->setUser($this->getSetting('user'));
        $config->setPassword($this->getSetting('password'));
        $config->setVhost($this->getSetting('vhost'));
        $config->setHeartbeat((int) $this->getSetting('heartbeat'));
        $config->setKeepalive((bool) $this->getSetting('keepalive'));

        $this->connection = $this->amqpConnectionFactory->create($config);
        $this->channel = $this->connection->channel();

        $this->logger->debug('AMQP server connected');
    }

    public function getConnection(): ?AbstractConnection
    {
        return $this->connection;
    }

    public function getChannel(): ?AMQPChannel
    {
        return $this->channel;
    }

    public function getSetting(string $key): string
    {
        $connection = $this->settings['connection'];
        $connectionSettings = $this->settings['connections'][$connection];

        return $this->settings[$key] ?? $connectionSettings[$key];
    }

    public function disconnect(): void
    {
        if ($this->channel && $this->connection) {
            $this->logger->debug('Disconnecting AMQP server...');

            $this->channel->close();
            $this->connection->close();
            $this->channel = null;
            $this->connection = null;

            $this->logger->debug('AMQP server disconnected');
        }
    }

    public function reconnect(): void
    {
        $this->logger->debug('Reconnecting AMQP server...');

        $this->disconnect();
        $this->connect();

        $this->logger->debug('AMQP server reconnected');
    }

    public function createQueue(string $queue, array $arguments = []): void
    {
        $this->logger->debug("Creating queue: {$queue}");

        $this->connect();
        $this->channel->queue_declare(
            queue: $queue,
            durable: true,
            auto_delete: false,
            arguments: new AMQPTable($arguments),
        );

        $this->logger->debug("Queue created: {$queue}");
    }

    public function deleteQueue(string $queue): void
    {
        $this->logger->debug("Deleting queue: {$queue}");

        $this->connect();
        $this->channel->queue_delete(queue: $queue);

        $this->logger->debug("Queue deleted: {$queue}");
    }

    public function createExchange(string $exchange, ExchangesTypes $type = ExchangesTypes::FANOUT): void
    {
        $this->logger->debug("Creating exchange: {$exchange}");

        $this->connect();
        $this->channel->exchange_declare(
            exchange: $exchange,
            type: $type->value,
            durable: true,
            auto_delete: false,
        );

        $this->logger->debug("Exchange created: {$exchange}");
    }

    public function deleteExchange(string $exchange): void
    {
        $this->logger->debug("Deleting exchange: {$exchange}");

        $this->connect();
        $this->channel->exchange_delete(exchange: $exchange);

        $this->logger->debug("Exchange deleted: {$exchange}");
    }

    public function bind(string $queue, string $exchange, string $routeKey = ''): void
    {
        $this->logger->debug("Binding queue {$queue} to exchange {$exchange}");
        $this->connect();

        $this->channel->queue_bind(queue: $queue, exchange: $exchange, routing_key: $routeKey);
    }

    public function unbind(string $queue, string $exchange, string $routeKey = ''): void
    {
        $this->logger->debug("Unbinding queue {$queue} to exchange {$exchange}");
        $this->connect();

        $this->channel->queue_unbind(queue: $queue, exchange: $exchange, routing_key: $routeKey);
    }

    public function publish(AMQPMessage $message, ?string $exchange = null, ?string $routeKey = null): void
    {
        retry(
            times: (int) $this->getSetting('max-attempts'),
            callback: function () use ($message, $exchange, $routeKey) {
                $this->connect();

                $this->channel->basic_publish(
                    msg: $message,
                    exchange: $exchange,
                    routing_key: $routeKey,
                );

                $this->logger->debug("Message published to exchange {$exchange} or queue {$routeKey}".$message->getBody());
            },
            sleepMilliseconds: function (int $attempts) {
                $this->reconnect();
                $this->logger->alert(sprintf('Publisher failed. Retrying attempt %u ...', $attempts));

                return $attempts * 500;
            }
        );
    }

    /**
     * @param  string|string[]  $queue
     */
    public function consume(string|array $queue, $callback): void
    {
        $queues = Arr::wrap($queue);

        $this->connect();

        $queueLabel = Str::plural('queue', count($queues));

        $this->logger->debug(sprintf('Consuming %s: %s', $queueLabel, implode(', ', $queues)));
        $this->channel->basic_qos(prefetch_size: 0, prefetch_count: 1, a_global: false);

        foreach ($queues as $queue) {
            $this->channel->basic_consume(queue: $queue, callback: fn (AMQPMessage $message) => $callback($queue, $message));
        }

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }

        $this->logger->debug(sprintf('%s consume finished: %s', $queueLabel, implode(', ', $queues)));
    }

    public function accept(AMQPMessage $message): void
    {
        $message->ack();
        $this->logger->debug('Message accepted: '.$message->getBody());
    }

    public function reject(?AMQPMessage $message, Throwable $exception): void
    {
        if (! $message) {
            return;
        }

        $attempts = 0;

        try {
            $attempts = $message->get('application_headers')->getNativeData()['x-death'][0]['count'] ?? 0;
        } catch (Throwable) { }

        $attempts++;

        $this->logger->debug(sprintf(
            'Message rejected %u attempts: %s',
            $attempts,
            $exception->getMessage(),
        ));

        if ($attempts < $this->getSetting('max-attempts')) {
            $message->nack(requeue: false, multiple: false);

            return;
        }

        $message->ack();

        try {
            $dlq = $message->get('routing_key').'.dlq';
            $this->channel->basic_publish($message, '', $dlq);
            $this->logger->debug("Message forwarded to DLQ: $dlq");

        } catch (Throwable $e) {
            $this->logger->error("Error forwarding message to DLQ: {$exception->getMessage()}");
            report($e);
        }
    }

    public function getQueueInfo(string $queue): QueueInfo
    {
        $this->connect();
        [$queueName, $messageCount, $consumerCount] = $this->channel->queue_declare(queue: $queue, passive: true);

        return new QueueInfo($queueName, $messageCount, $consumerCount);
    }

    public function queueExists(string $queue): bool
    {
        try {
            $this->getQueueInfo($queue);

            return true;

        } catch (Throwable $exception) {
            if (str_contains($exception->getMessage(), 'NOT_FOUND')) {
                return false;
            }
            throw $exception;
        }
    }

    public function exchangeExists(string $exchange): bool
    {
        try {
            $this->connect();
            $this->channel->exchange_declare(exchange: $exchange, type: ExchangesTypes::FANOUT->value, passive: true);

            return true;

        } catch (Throwable $exception) {
            if (str_contains($exception->getMessage(), 'NOT_FOUND')) {
                return false;
            }
            throw $exception;
        }
    }
}
