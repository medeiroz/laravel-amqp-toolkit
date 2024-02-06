<?php

namespace Medeiroz\AmqpToolkit\Repositories;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class SchemaDbRepository
{
    public function __construct(
        private Resolver $resolver,
        private string $table,
    ) {
    }

    public function list(): Collection
    {
        return $this->builder()
            ->orderBy('migrated_at')
            ->get();
    }

    public function getLatestBatch(): int
    {
        return (int) $this->builder()
            ->max('batch');
    }

    public function create(string $schema): void
    {
        $batch = $this->getLatestBatch() + 1;
        $this->builder()->insert([
            'schema' => $schema,
            'batch' => $batch,
            'migrated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function delete(string $schema): void
    {
        $this->builder()->where('schema', $schema)->delete();
    }

    protected function builder(): Builder
    {
        return $this->getConnection()->table($this->table)->useWritePdo();
    }

    protected function getConnection(): ConnectionInterface
    {
        return $this->resolver->connection();
    }
}
