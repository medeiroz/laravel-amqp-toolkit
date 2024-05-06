# Pacote PHP/Laravel para Consumir e Publicar Mensagens com RabbitMQ

[![Latest Version on Packagist](https://img.shields.io/packagist/v/medeiroz/laravel-amqp-toolkit.svg?style=flat-square)](https://packagist.org/packages/medeiroz/laravel-amqp-toolkit)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/medeiroz/laravel-amqp-toolkit/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/medeiroz/laravel-amqp-toolkit/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/medeiroz/laravel-amqp-toolkit/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/medeiroz/laravel-amqp-toolkit/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/medeiroz/laravel-amqp-toolkit.svg?style=flat-square)](https://packagist.org/packages/medeiroz/laravel-amqp-toolkit)

This package was developed to facilitate the integration of Laravel applications with RabbitMQ,
providing functionalities to consume and publish messages, in addition to providing a simple way to
manage the AMQP / RabbitMQ infrastructure through schema migrations, inspired by Laravel's database migrations.

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
Para realizar a instalação do pacote você deve seguir os seguintes passos:
1. Install the `medeiroz/laravel-amqp-toolkit` package via composer
2. Publish and run migrations
3. Publish the configuration file
4. Environment variables .env


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

### 7. Environment variables `.env`
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

AmqpPublisher::onQueue(['say' => 'hello queue'], 'my-first-queue');
AmqpPublisher::onExchange(['say' => 'hello exchange'], 'my-exchange');
AmqpPublisher::onExchange(['say' => 'hello exchange with routing key'], 'my-exchange', 'my-routing-key');
```

---

### Start consuming an AMQP / RabbitMQ queue
To start consuming a specific queue you must run the artisan command below:

Where `my-first-queue` is the name of the queue you want to consume.
```bash
php artisan amqp:consumer my-first-queue
```

---

## Listening and processing messages

Edit the `app/Providers/EventServiceProvider.php` file and add the events you want to listen to.

The name of the event should be `amqp.QUEUE_NAME`, where `QUEUE_NAME` is the name of the queue you want to listen to.


> app/Providers/EventServiceProvider.php 
```php
public function boot() {
    Event::listen(
        'amqp:payment-received.notifications',
        \App\Listeners\PaymentReceivedNotificationsListener::class,
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
php artisan make:listener MyQueueListener
```

If you want your events to be executed asynchronously with `Laravel Horizon`, you can use the `ShouldQueue` interface.

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Medeiroz\AmqpToolkit\Events\AmqpReceivedMessageEvent;


class MyQueueListeners implements ShouldQueue
{
    public function handle(AmqpReceivedMessageEvent $event): void
    {
        \Log::debug('Queue' . $event->queue);
        \Log::debug('Message Body', $event->messageBody);
    }
}
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
