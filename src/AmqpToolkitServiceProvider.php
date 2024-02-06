<?php

namespace Medeiroz\AmqpToolkit;

use Illuminate\Support\Facades\Log;
use Medeiroz\AmqpToolkit\Commands\AmqpConsumerCommand;
use Medeiroz\AmqpToolkit\Commands\AmqpMigrateSchemaCommand;
use Medeiroz\AmqpToolkit\Commands\AmqpToolkitMakeSchemaCommand;
use Medeiroz\AmqpToolkit\Repositories\SchemaDbRepository;
use Medeiroz\AmqpToolkit\Repositories\SchemaFileRepository;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AmqpToolkitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-amqp-toolkit')
            ->hasConfigFile()
            ->hasMigration('create_amqp_schemas_table')
            ->hasCommand(AmqpToolkitMakeSchemaCommand::class)
            ->hasCommand(AmqpMigrateSchemaCommand::class)
            ->hasCommand(AmqpConsumerCommand::class);

        $this->app->singleton(SchemaCreator::class, function ($app) {
            return new SchemaCreator($app['files'], $app->basePath('stubs'));
        });

        $this->app->singleton(
            SchemaFileRepository::class,
            fn ($app) => new SchemaFileRepository($app['config']['amqp-toolkit']['schemas']),
        );

        $this->app->singleton(
            SchemaDbRepository::class,
            fn ($app) => new SchemaDbRepository($app['db'], $app['config']['amqp-toolkit']['table_name']),
        );

        $this->app->bind(
            AmqpClient::class,
            fn ($app) => new AmqpClient(
                amqpConnectionFactory: new AMQPConnectionFactory,
                logger: Log::channel($app['config']['amqp-toolkit']['logging-channel']),
                settings: $app['config']['amqp-toolkit'],
            ),
        );
    }
}
