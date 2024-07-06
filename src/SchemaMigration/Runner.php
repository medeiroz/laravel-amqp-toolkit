<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration;

use Illuminate\Support\Collection;
use Laravel\Prompts\Output\ConsoleOutput;
use Medeiroz\AmqpToolkit\AmqpClient;
use Medeiroz\AmqpToolkit\RabbitmqApi;
use Medeiroz\AmqpToolkit\Repositories\SchemaDbRepository;
use Medeiroz\AmqpToolkit\Repositories\SchemaFileRepository;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\SchemaBlueprintInterface;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\WithAmqpClientInterface;
use Medeiroz\AmqpToolkit\SchemaMigration\Contracts\WithRabbitmqApiInterface;
use Throwable;

class Runner
{
    public function __construct(
        private readonly AmqpClient $amqpClient,
        private readonly RabbitmqApi $rabbitmqApi,
        private readonly SchemaFileRepository $schemaFileRepository,
        private readonly SchemaDbRepository $schemaDbRepository,
        private readonly ConsoleOutput $output,

    ) {}

    public function migrate(): void
    {
        $filesToMigrate = $this->getFilesToMigrate();
        $batchId = $this->schemaDbRepository->getLatestBatch() + 1;

        $filesToMigrate->each(function (array $file) use ($batchId) {
            $schemaMigration = require $file['path'];
            $this->migrateSchemaUp($schemaMigration, $file);
            $this->schemaDbRepository->create($file['name'], $batchId);
        });
    }

    public function rollback(int $step = 1): void
    {
        $files = $this->schemaFileRepository->list();
        $batchesToRollback = $this->getBatchesToRollback($step);
        $filesToRollback = $this->getFilesToRollback($files, $batchesToRollback);

        $filesToRollback->each(function ($file) {
            $schemaMigration = require $file['path'];
            $this->migrateSchemaDown($schemaMigration, $file);
            $this->schemaDbRepository->delete($file['name']);
        });
    }

    public function refresh(): void
    {
        $this->rollback(step: 0);
        $this->migrate();
    }

    protected function migrateSchemaUp(SchemaMigration $schemaMigration, array $file): void
    {
        try {
            $this->output->writeln("Running migration: {$file['name']}");

            $schemaMigration->up();
            $this->runStack($schemaMigration->getStack());

            $this->output->writeln("Migration: {$file['name']} successes");
        } catch (Throwable $exception) {
            $this->output->writeln("Migration: {$file['name']} failed");
            throw $exception;
        }
    }

    protected function migrateSchemaDown(SchemaMigration $schemaMigration, array $file): void
    {
        try {
            $this->output->writeln("Rolling back migration: {$file['name']}");

            $schemaMigration->down();
            $this->runStack($schemaMigration->getStack());

            $this->output->writeln("Rollback migration: {$file['name']} successes");
        } catch (Throwable $exception) {
            $this->output->writeln("Rollback migration: {$file['name']} failed");
            throw $exception;
        }
    }

    protected function runStack(array $stack): void
    {
        array_map(function (SchemaBlueprintInterface $item) {
            if ($item instanceof WithAmqpClientInterface) {
                $item->setAmqpClient($this->amqpClient);
            }

            if ($item instanceof WithRabbitmqApiInterface) {
                $item->setRabbitmqApi($this->rabbitmqApi);
            }

            $item->run();
        }, $stack);
    }

    protected function getFilesToMigrate(): Collection
    {
        $files = $this->schemaFileRepository->list();
        $migrations = $this->schemaDbRepository->list();

        return $files->filter(
            fn ($file) => ! $migrations->contains('schema', $file['name']),
        );
    }

    protected function getBatchesToRollback(int $step): Collection
    {
        $migrations = $this->schemaDbRepository->list();
        $batches = $migrations->pluck('batch')->unique()->values();

        return $step > 0 ? $batches->take(-$step) : $batches;
    }

    protected function getFilesToRollback(Collection $files, Collection $batchesToRollback): Collection
    {
        $migrations = $this->schemaDbRepository->list();
        $migrationsToRollback = $migrations->whereIn('batch', $batchesToRollback);

        return $files->whereIn('name', $migrationsToRollback->pluck('schema'));
    }
}
