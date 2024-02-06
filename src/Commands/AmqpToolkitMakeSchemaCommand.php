<?php

namespace Medeiroz\AmqpToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Medeiroz\AmqpToolkit\SchemaCreator;

class AmqpToolkitMakeSchemaCommand extends Command
{
    public $signature = 'amqp:make-schema {type} {name}';

    public $description = 'Make a new amqp schema queue or exchange';

    protected SchemaCreator $creator;

    protected Composer $composer;

    public function __construct(SchemaCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    public function handle()
    {
        $type = trim($this->argument('type'));
        $name = Str::snake(trim($this->argument('name')));

        $this->writeSchema($type, $name);
    }

    protected function writeSchema(string $type, string $name): void
    {
        $file = $this->creator->create(
            $type,
            $name,
            $this->getSchemaPath(),
        );

        $this->components->info(sprintf('amqp Schema [%s] created successfully.', $file));
    }

    protected function getSchemaPath(): string
    {
        return config('amqp-toolkit.schemas');
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'type' => ['What should the schema be typed?', 'queue, exchange or shovel'],
            'name' => ['What should the migration be named?', 'E.g. queue.payment-processed'],
        ];
    }
}
