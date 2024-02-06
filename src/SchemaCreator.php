<?php

namespace Medeiroz\AmqpToolkit;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SchemaCreator
{
    protected array $postCreate = [];

    public function __construct(
        protected Filesystem $files,
        protected string $customStubPath,
    ) {
    }

    public function create(string $type, string $name, string $path): string
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        $stub = $this->getStub($type);

        $path = $this->getPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put(
            $path, $this->populateStub($stub, $name)
        );

        $this->firePostCreateHooks($name, $path);

        return $path;
    }

    protected function ensureMigrationDoesntAlreadyExist(string $name, ?string $migrationPath = null): void
    {
        if (! empty($migrationPath)) {
            $migrationFiles = $this->files->glob($migrationPath.'/*.php');

            foreach ($migrationFiles as $migrationFile) {
                $this->files->requireOnce($migrationFile);
            }
        }

        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    protected function getStub(string $type): string
    {
        $stubName = match ($type) {
            'queue' => 'amqp_schemas_create_queue.stub',
            'exchange' => 'amqp_schemas_create_exchange.stub',
            'shovel' => 'amqp_schemas_create_shovel.stub',
            default => throw new InvalidArgumentException("Invalid schema type: {$type}"),
        };

        $stub = $this->files->exists($customPath = $this->customStubPath.DIRECTORY_SEPARATOR.$stubName)
            ? $customPath
            : $this->stubPath().DIRECTORY_SEPARATOR.$stubName;

        return $this->files->get($stub);
    }

    protected function populateStub(string $stub, ?string $name): string
    {
        if (! is_null($name)) {
            $stub = str_replace(
                ['DummyName', '{{ name }}', '{{name}}'],
                $name,
                $stub,
            );
        }

        return $stub;
    }

    protected function getClassName(string $name): string
    {
        return Str::studly($name);
    }

    protected function getPath(string $name, string $path): string
    {
        return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
    }

    protected function firePostCreateHooks(string $name, string $path): void
    {
        foreach ($this->postCreate as $callback) {
            $callback($name, $path);
        }
    }

    public function afterCreate(Closure $callback): void
    {
        $this->postCreate[] = $callback;
    }

    protected function getDatePrefix(): string
    {
        return date('Y_m_d_His');
    }

    public function stubPath(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'stubs';
    }

    public function getFilesystem(): Filesystem
    {
        return $this->files;
    }
}
