<?php

namespace Medeiroz\AmqpToolkit\SchemaMigration\Contracts;

use Medeiroz\AmqpToolkit\RabbitmqApi;

interface WithRabbitmqApiInterface
{
    public function setRabbitmqApi(RabbitmqApi $rabbitmqApi): self;

    public function getRabbitmqApi(): RabbitmqApi;
}
