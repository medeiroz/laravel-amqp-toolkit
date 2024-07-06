# Package PHP/Laravel to manager, consume and publish messages with AMQP / RabbitMQ

[![Latest Version on Packagist](https://img.shields.io/packagist/v/medeiroz/laravel-amqp-toolkit.svg?style=flat-square)](https://packagist.org/packages/medeiroz/laravel-amqp-toolkit)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/medeiroz/laravel-amqp-toolkit/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/medeiroz/laravel-amqp-toolkit/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/medeiroz/laravel-amqp-toolkit/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/medeiroz/laravel-amqp-toolkit/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/medeiroz/laravel-amqp-toolkit.svg?style=flat-square)](https://packagist.org/packages/medeiroz/laravel-amqp-toolkit)

This package was developed to facilitate the integration of Laravel applications with RabbitMQ,
providing functionalities to consume and publish messages, in addition to providing a simple way to
manage the AMQP / RabbitMQ infrastructure through schema migrations, inspired by Laravel's database migrations.

If you need to consume messages from RabbitMQ queues, publish messages in exchanges or queues, or manage the RabbitMQ infrastructure, this package is for you.

If you have a problems to manage the RabbitMQ Schema same as queues, exchanges and shovels, this package is for you.

---

## Main Features

1. **Schema Migrations:** Use schema migrations to create, delete and manage queues, exchanges and shovels in RabbitMQ, similar to what Laravel offers for databases.
2. **Consume Queue:** Ability to consume messages from a RabbitMQ queue in a simple and efficient way.
3. **Publish Messages:** Ability to publish messages in exchanges or directly in RabbitMQ queues.
4. **Event Listeners:** Ability to listen to messages received from AMQP / RabbitMQ queues and exchanges, similar to Laravel's event listeners.
---

## Requirements
- PHP >= 8.1
- Laravel >= 10


## Installation
follow the steps below to install the package:
1. Install the `medeiroz/laravel-amqp-toolkit` package via composer
2. Publish and run migrations
3. Publish the configuration file
4. Environment variables .env
5. Configure the AMQP Queue and Laravel Listeners


### 1. Install the package
Run the command below to install the package via composer:
```bash
composer require medeiroz/laravel-amqp-toolkit
```

### 2. Publish migrations
You must publish the database migration with:

```bash
php artisan vendor:publish --tag="amqp-toolkit-migrations"
```
Then run the command below to create the table in the database.
```bash
php artisan migrate
```

### 3. Publish the configuration file
You can publish the configuration file with:

```bash
php artisan vendor:publish --tag="amqp-toolkit-config"
```

This is the content of the published configuration file:

```php
<?php

// config for Medeiroz/AmqpToolkit

return [
    'schemas' => base_path('amqp-toolkit-schemas'),
    'table_name' => env('AMQP_TABLE_NAME', 'amqp_schemas'),
    'max-attempts' => env('AMQP_MAX_ATTEMPTS', 10),
    'heartbeat' => env('AMQP_HEARTBEAT', 30),
    'keepalive' => env('AMQP_KEEPALIVE', true),

    /**
     * The default connection to use when no connection is provided to the AMQP client.
     */
    'connection' => env('AMQP_CONNECTION', 'rabbitmq'),

    /**
     * The default logging channel to use when no channel is provided to the AMQP client.
     * You can use the same channels as the Laravel logging configuration
     * Like as 'stack', 'single', 'daily' etc...
     */
    'logging-channel' => env('AMQP_LOG_CHANNEL', env('LOG_CHANNEL')),

    /**
     * The queues to be consumed by the consumer command without arguments.
     */
    'consumer-queues' => [
        // 'payment-received' => \App\Listeners\PaymentReceivedListener::class,
    ],

    'connections' => [
        'rabbitmq' => [
            'host' => env('AMQP_HOST', 'localhost'),
            'port' => env('AMQP_PORT', 5672),
            'api-port' => env('AMQP_API_PORT', 15672),
            'user' => env('AMQP_USER', 'guest'),
            'password' => env('AMQP_PASSWORD', ''),
            'vhost' => env('AMQP_VHOST', '/'),
        ],
    ],
];
```

### 4. Environment variables `.env`
Edit the `.env` file and add the environment variables of your AMQP / Rabbitmq server.

```dotenv
AMQP_HOST=your-amqp-host
AMQP_PORT=5672
AMQP_API_PORT=15672
AMQP_USER=user
AMQP_PASSWORD=password
AMQP_VHOST=/
```
>Remember to replace the values according to your environment.

### 5. Configure the AMQP Queue and Laravel Listeners

Edit the `config/amqp-toolkit.php` file and add the queues you want to consume and the listeners that will be executed when a message is received.

The key of the array is the name of the queue and the value is the listener class that will be executed when a message is received.

Example: Attach the listener `PaymentReceivedListener` to the queue `payment-received`
```php
    /**
     * The queues to be consumed by the consumer command without arguments.
     */
    'consumer-queues' => [
        'payment-received' => \App\Listeners\PaymentReceivedListener::class,
    ],
```

Refer to the configuration file for more details.

---

## Usage

### Schema Migrations

Allowed schema types are:
- queue
- exchange
- shovel

### Creating a Schema
```bash
php artisan amqp:make-schema {type} {name}
```
Examples
```bash
php artisan amqp:make-schema queue my-first-queue
php artisan amqp:make-schema exchange my-exchange
php artisan amqp:make-schema shovel my-shovel
```

### Running Migrations
```bash
php artisan amqp:migrate 
```
Reverting Migrations
```bash
php artisan amqp:migrate --rollback --step=1
```

Reverting all migrations
```bash
php artisan amqp:migrate --refresh
```

---

### Publishing a message to a queue or exchange
```php
use Medeiroz\AmqpToolkit\Facades\AmqpPublisher;

AmqpPublisher::onQueue(['say' => 'Hello World'], 'my-queue-name');
AmqpPublisher::onExchange(['say' => 'Hello World'], 'my-exchange-name');
AmqpPublisher::onExchange(['say' => 'Hello World with routing key'], 'my-exchange-name', 'my-routing-key');
```

---

### Start consuming an AMQP / RabbitMQ queue

#### Running the consumer for all queues
To start consuming all queues you must run the artisan command below:

```bash
php artisan amqp:consumer
```

To start consuming a specific queues you must run the artisan command below:

Where `my-first-queue` and `payment-received` is the name of the queues you want to consume.
```bash
php artisan amqp:consumer my-first-queue payment-received
```

---

## Listening messages

The package provides a way to listen to messages received from AMQP / RabbitMQ queues and exchanges, similar to Laravel's event listeners.

### 1. Automatic listener registration

When a queue and listener are configured in the `config/amqp-toolkit.php` file, the listener will be automatically registered when the consumer is executed.

### 2. Manual listener registration

Edit the `app/Providers/EventServiceProvider.php` file and add the events you want to listen to.

The name of the event should be `amqp.QUEUE_NAME`, where `QUEUE_NAME` is the name of the queue you want to listen to.


> app/Providers/EventServiceProvider.php 
```php
public function boot() {
    Event::listen(
        'amqp:payment-received',
        \App\Listeners\PaymentReceivedListener::class,
    );
    
    Event::listen(
        'amqp:my-queue',
        \App\Listeners\MyQueueListener::class,
    );
    
    Event::listen(
        'amqp:*',
        \App\Listeners\AllListener::class,
    );
}

```
Note: The amqp:* event is a special event that listens to all messages received from all queues.
The queue event is only called if the consumer is being executed

## Creating a listener for the event

You can create a listener for the event you want to listen to, for this run the command below:

```bash
php artisan make:listener PaymentReceivedListener
```

This package provides an event object `Medeiroz\AmqpToolkit\Events\AmqpReceivedMessageEvent` that contains the queue name and the message body.

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Medeiroz\AmqpToolkit\Events\AmqpReceivedMessageEvent;


class PaymentReceivedListener
{
    public function handle(AmqpReceivedMessageEvent $event): void
    {
        \Log::debug('Queue' . $event->queue);
        \Log::debug('Message Body', $event->messageBody);
    }
}
```

If you want your events to be executed asynchronously with `Laravel Horizon`, you can use the `ShouldQueue` interface.

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Medeiroz\AmqpToolkit\Events\AmqpReceivedMessageEvent;


class PaymentReceivedListener implements ShouldQueue
{ /* ... */ }
```

## More about the AMQP Migration Schema


### Creating an Exchange Schema

Create a new exchange schema with the command below:
```bash
php artisan amqp:make-schema exchange my-exchange
```

Generated file:
```php
<?php

use Medeiroz\AmqpToolkit\SchemaMigration\SchemaMigration;

return new class extends SchemaMigration
{
    private const NAME = 'my-exchange';

    public function up(): void
    {
        $this->createExchangeIfNonExists(self::NAME);
    }

    public function down(): void
    {
        $this->deleteExchangeIfExists(self::NAME);
    }
};

```

### Creating a Queue Schema
Create a new queue schema with the command below:
```bash
php artisan amqp:make-schema queue payment-received
```

Generated file:
```php
<?php


use Medeiroz\AmqpToolkit\SchemaMigration\SchemaMigration;

return new class extends SchemaMigration
{
    private const NAME = 'payment-received';

    public function up(): void
    {
        $this->createQueueIfNonExists(self::NAME)
            ->withRetry()
            ->withTtl(seconds: 5)
            ->withDlq();
    }

    public function down(): void
    {
        $this->deleteQueueIfExists(self::NAME);
        $this->deleteQueueIfExists(self::NAME . '.retry');
        $this->deleteQueueIfExists(self::NAME . '.dlq');
    }
};
```
This schema creates a queue named `payment-received` with retry, ttl and dlq. RabbitMQ will automatically create the `payment-received.retry` and `payment-received.dlq` queues.

You can create bind the queue to an exchange by calling the `bind` method.
```php
// ...
    public function up(): void
    {
        $this->createQueue('my-queue')
            ->bind('my-exchange', 'my-route-key');
    }
    
    // or
    
    public function up(): void
    {
        $this->createQueue('my-queue');

        $this->bind('my-queue', 'my-exchange', 'my-route-key');
    }
```

### Creating a Shovel Schema
Create a new shovel schema with the command below:
```bash
php artisan amqp:make-schema shovel my-shovel
```

Generated file:
```php
<?php

use Medeiroz\AmqpToolkit\SchemaMigration\SchemaMigration;
use Medeiroz\AmqpToolkit\SchemaMigration\Shovel\Resource0dot9;
use Medeiroz\AmqpToolkit\SchemaMigration\Shovel\Resource1dot0;

return new class extends SchemaMigration
{
    private const NAME = 'my-shovel';

    public function up(): void
    {
        $this->createShovelIfNonExists(
            name: self::NAME,
            source: new Resource0dot9(
                type: 'queue',
                uri: 'amqp://',
                queue: 'my-queue-on-amqp-0-9',
                autoDelete: 'never',
                addForwardingHeaders: 'No',
            ),
            destination: new Resource1dot0(
                uri: 'amqps://user:password@my-host.servicebus.windows.net:5671/?verify=verify_none',
                address: 'my-topic-on-service-bus',
            ),
        );
    }

    public function down(): void
    {
        $this->deleteShovelIfExists(self::NAME);
    }
};
```
You can create a shovel with the `createShovel` or `createShovelIfNonExists` method, passing the name of the shovel, the source and destination resources.

The `source` and `destination` are objects that represent message `broker` resources.

Available resources:
- Resource0dot9
  - The Message Broker resource for AMQP 0-9-1 same as RabbitMQ, Qpid, etc.
- Resource1dot0
  - The Message Broker resource for AMQP 1.0 same as Azure Service Bus, ActiveMQ, etc.

You create a shovel to move messages from Broker A to Broker B, for example.
- Move messages from RabbitMQ to Azure Service Bus
- Move messages from Azure Service Bus to RabbitMQ
- Move messages from RabbitMQ in `AWS Cloud` to RabbitMQ in `Azure Cloud`
- Move messages from Azure Service Bus to another subscription in Azure Service Bus

More about the shovel configuration can be found in the [RabbitMQ Shovel documentation](https://www.rabbitmq.com/shovel-dynamic.html).


> Note: The shovel schema is only available for RabbitMQ 3.8.0 or later.

### All available methods in schema migration
- `createQueue(string $name): Medeiroz\AmqpToolkit\SchemaMigration\Queue`
  - Create a new queue
- `createQueueIfNonExists(string $name): Medeiroz\AmqpToolkit\SchemaMigration\Queue`
  - Create a new queue if it does not exist
- `deleteQueue(string $name): void`
  - Delete a queue
- `deleteQueueIfExists(string $name): void`
  - Delete a queue if it exists
- `createExchange(string $name): Medeiroz\AmqpToolkit\SchemaMigration\Exchange`
  - Create a new exchange
- `createExchangeIfNonExists(string $name): Medeiroz\AmqpToolkit\SchemaMigration\Exchange`
  - Create a new exchange if it does not exist
- `deleteExchange(string $name): void`
  - Delete an exchange
- `deleteExchangeIfExists(string $name): void`
  - Delete an exchange if it exists
- `bind(string $queue, string $exchange, ?string $routingKey = null): Medeiroz\AmqpToolkit\SchemaMigration\Bind`
  - Bind a queue to an exchange with a routing key
- `bindIfExist(string $queue, string $exchange, ?string $routingKey = null): Medeiroz\AmqpToolkit\SchemaMigration\Bind`
  - Bind a queue to an exchange with a routing key if the queue exists
- `unbind(string $queue, string $exchange, ?string $routingKey = null): void`
  - Unbind a queue from an exchange with a routing key
- `unbindIfExists(string $queue, string $exchange, ?string $routingKey = null): void`
  - Unbind a queue from an exchange with a routing key if the queue exists
- `createShovel(string $name): Medeiroz\AmqpToolkit\SchemaMigration\Shovel`
  - Create a new shovel
- `createShovelIfNonExists(string $name): Medeiroz\AmqpToolkit\SchemaMigration\Shovel`
  - Create a new shovel if it does not exist
- `deleteShovel(string $name): void`
  - Delete a shovel
- `deleteShovelIfExists(string $name): void`
  - Delete a shovel if it exists

## More about the Shovel
The shovel is a feature that allows you to move messages between different brokers, for example, RabbitMQ to Azure Service Bus, Azure Service Bus to RabbitMQ, etc.

If your message broke is Azure Service Bus and your application as code with PHP/Laravel, you can not consume messages directly from the Azure Service Bus.
You can create a shovel to move messages from Azure Service Bus to RabbitMQ and consume messages from RabbitMQ.

> Forward message from `Azure Service Bus` to `RabbitMQ`

You need create a specific resources for your shovel, for example, you have the resources below:
- On Azure Service Bus
  - Subscription A `(Source)`
- On RabbitMQ
  - Exchange `(Destination)`
  - Queue A (for your PHP application to consume messages)
  - Shovel "My First Shovel"

```mermaid
flowchart TD
    subgraph "RabbitMQ"
        E(Exchange)
        E --> QB[Queue B]
        E --> QA[Queue A]
    end
    
    subgraph "Service Bus"
        T(Topic)
        T -->|Publish| SB[Subscription B]
        T -->|Publish| SA[Subscription A]
    end
    
    subgraph "Shovel Plugin"
        S(My First Shovel) -->|Consume Messages|SA
        S --> |Forward to| E
    end
    
    subgraph "PHP consume RabbitMQ"
        P(PHP Application) --> |Consume Messages| QA
    end
```
----

> Forward message from `RabbitMQ` to `Azure Service Bus`

You need create a specific resources for your shovel, for example, you have the resources below:
- On RabbitMQ
    - Exchange
    - Queue D `(Source)`
    - Shovel "My Second Shovel"
- On Azure Service Bus
    - Topic `(Destination)`


```mermaid
flowchart TD
    subgraph "RabbitMQ"
        E(Exchange)
        E --> QD[Queue D]
        E --> QC[Queue C]
    end

    subgraph "Service Bus"
        T(Topic)
        T -->|Publish| SD[Subscription D]
        T -->|Publish| SC[Subscription C]
    end

    subgraph "Shovel Plugin"
        S(My Second Shovel) -->|Consume Messages|QD
        S --> |Forward to| T
    end

    subgraph "PHP publish RabbitMQ"
        P(PHP Application) --> |Publish Messages| E
    end
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.


## Credits

- [Flavio Medeiros](https://github.com/medeiroz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
