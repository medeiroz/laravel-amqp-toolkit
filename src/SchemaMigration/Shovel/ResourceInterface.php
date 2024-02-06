<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration\Shovel;

interface ResourceInterface
{
    public function toArray(): array;

    public function getProtocol(): string;

    public function getUri(): string;

    public function getAddForwardingHeaders(): bool;

    public function getAutoDelete(): string;

    public function getPrefetchCount(): int;
}
