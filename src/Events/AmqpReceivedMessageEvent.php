<?php

namespace Medeiroz\AmqpToolkit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmqpReceivedMessageEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $queue,
        public array $messageBody,
    ) {}
}
