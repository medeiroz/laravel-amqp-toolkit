<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration\Contracts;

interface SchemaBlueprintInterface
{
    public function run(): void;
}
