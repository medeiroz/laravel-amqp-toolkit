<?php

namespace Medeiroz\AmqpToolkit;

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
    ) {
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
        $this->logger->debug('Disconnecting AMQP server...');

        if ($this->channel && $this->connection) {
            $this->channel->close();
            $this->connection->close();
            $this->channel = null;
            $this->connection = null;
        }

        $this->logger->debug('AMQP server disconnected');
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
        $this->connect();

        $this->channel->basic_publish(
            msg: $message,
            exchange: $exchange,
            routing_key: $routeKey,
        );

        $this->logger->debug("Message published to exchange {$exchange} or queue {$routeKey}".$message->getBody());
    }

    public function consume(?string $queue, $callback): void
    {
        $this->connect();

        $this->logger->debug("Consuming queue: {$queue}");

        $this->channel->basic_qos(prefetch_size: 0, prefetch_count: 1, a_global: false);
        $this->channel->basic_consume(
            queue: $queue,
            callback: $callback,
        );
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }

        $this->logger->debug("Queue consume finished: {$queue}");
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

        $attempts = $message->get('application_headers')->getNativeData()['x-death'][0]['count'] ?? 0;
        $attempts++;

        $this->logger->debug(
            "Message rejected $attempts attempts"
            .": {$exception->getMessage()}");

        if ($attempts < $this->getSetting('max-attempts')) {
            $message->nack(requeue: false, multiple: false);

            return;
        }

        $message->ack();

        try {
            $this->channel->basic_publish($message, '', $message->get('routing_key').'.dlq');
            $this->logger->debug('Message forward to DLQ: '.$message->getBody());
        } catch (Throwable $e) {
            $this->logger->error('Error forwarding message to DLQ: '.$e->getMessage());
        }
    }
}
