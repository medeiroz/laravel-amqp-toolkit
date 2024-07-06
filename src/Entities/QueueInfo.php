<?php

namespace Medeiroz\AmqpToolkit\Entities;

class QueueInfo
{
    public function __construct(
        public readonly string $name,
        public readonly int $messageCount,
        public readonly int $consumerCount,
    ) {}
}
