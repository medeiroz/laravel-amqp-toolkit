<?php

namespace Medeiroz\AmqpToolkit\Repositories;

use Illuminate\Support\Collection;

class SchemaFileRepository
{
    public function __construct(
        private string $path = '',
    ) {
        if (! is_dir($this->path)) {
            throw new \InvalidArgumentException("The path {$this->path} is not a directory.");
        }
    }

    public function list(): Collection
    {
        $files = collect(scandir($this->path));

        return $files
            ->filter(fn ($file) => ! in_array($file, ['.', '..']))
            ->map(fn (string $file) => [
                'name' => $file,
                'path' => $this->path.DIRECTORY_SEPARATOR.$file,
            ]);
    }
}
