<?php

namespace Medeiroz\AmqpToolkit\Enums;

enum ExchangesTypes: string
{
    case DIRECT = 'direct';
    case FANOUT = 'fanout';
    case TOPIC = 'topic';
    case HEADERS = 'headers';
}
