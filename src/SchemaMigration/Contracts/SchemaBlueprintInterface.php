<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration\Contracts;

use Medeiroz\AmqpToolkit\AmqpClient;

interface SchemaBlueprintInterface
{
    public function run(AmqpClient $client): void;
}
