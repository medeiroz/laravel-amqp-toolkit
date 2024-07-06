<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use Medeiroz\AmqpToolkit\SchemaMigration\Shovel\ResourceInterface;

abstract class SchemaMigration
{
    private array $stack = [];

    abstract public function up(): void;

    abstract public function down(): void;

    public function createExchange(string $name): Exchange
    {
        $exchange = new Exchange('create', $name);
        $this->stack[] = $exchange;

        return $exchange;
    }

    public function createExchangeIfNonExists(string $name): Exchange
    {
        $exchange = new Exchange('create-if-non-exists', $name);
        $this->stack[] = $exchange;

        return $exchange;
    }

    public function deleteExchange(string $name): void
    {
        $exchange = new Exchange('delete', $name);
        $this->stack[] = $exchange;
    }

    public function deleteExchangeIfExists(string $name): void
    {
        $exchange = new Exchange('delete-if-exists', $name);
        $this->stack[] = $exchange;
    }

    public function createQueue(string $name): Queue
    {
        $queue = new Queue('create', $name);
        $this->stack[] = $queue;

        return $queue;
    }

    public function createQueueIfNonExists(string $name): Queue
    {
        $queue = new Queue('create-if-non-exists', $name);
        $this->stack[] = $queue;

        return $queue;
    }

    public function deleteQueue(string $name): void
    {
        $queue = new Queue('delete', $name);
        $this->stack[] = $queue;
    }

    public function deleteQueueIfExists(string $name): void
    {
        $queue = new Queue('delete-if-exists', $name);
        $this->stack[] = $queue;
    }

    public function createShovel(string $name, ResourceInterface $source, ResourceInterface $destination): Shovel
    {
        $shovel = new Shovel('create', $name, $source, $destination);
        $this->stack[] = $shovel;

        return $shovel;
    }

    public function createShovelIfNonExists(string $name, ResourceInterface $source, ResourceInterface $destination): Shovel
    {
        $shovel = new Shovel('create-if-non-exists', $name, $source, $destination);
        $this->stack[] = $shovel;

        return $shovel;
    }

    public function deleteShovel(string $name): void
    {
        $shovel = new Shovel('delete', $name);
        $this->stack[] = $shovel;
    }

    public function deleteShovelIfExists(string $name): void
    {
        $shovel = new Shovel('delete-if-exists', $name);
        $this->stack[] = $shovel;
    }

    public function bind(string $queue, string $exchange, ?string $routeKey = null): Bind
    {
        $bind = new Bind('bind', $queue, $exchange, $routeKey);
        $this->stack[] = $bind;

        return $bind;
    }

    public function bindIfExists(string $queue, string $exchange, ?string $routeKey = null): Bind
    {
        $bind = new Bind('bind-if-exists', $queue, $exchange, $routeKey);
        $this->stack[] = $bind;

        return $bind;
    }

    public function unbind(string $queue, string $exchange, ?string $routeKey = null): void
    {
        $bind = new Bind('unbind', $queue, $exchange, $routeKey);
        $this->stack[] = $bind;
    }

    public function unbindIfExists(string $queue, string $exchange, ?string $routeKey = null): void
    {
        $bind = new Bind('unbind-if-exists', $queue, $exchange, $routeKey);
        $this->stack[] = $bind;
    }

    public function getStack(): array
    {
        return $this->stack;
    }
}
