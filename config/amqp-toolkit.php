<?php

// config for Medeiroz/AmqpToolkit

return [
    'schemas' => base_path('amqp-toolkit-schemas'),
    'table_name' => env('AMQP_TABLE_NAME', 'amqp_schemas'),
    'max-attempts' => env('AMQP_MAX_ATTEMPTS', 10),
    'heartbeat' => env('AMQP_HEARTBEAT', 30),
    'keepalive' => env('AMQP_KEEPALIVE', true),

    /**
     * The default connection to use when no connection is provided to the AMQP client.
     */
    'connection' => env('AMQP_CONNECTION', 'rabbitmq'),

    /**
     * The default logging channel to use when no channel is provided to the AMQP client.
     * You can use the same channels as the Laravel logging configuration
     * Like as 'stack', 'single', 'daily' etc...
     */
    'logging-channel' => env('AMQP_LOG_CHANNEL', env('LOG_CHANNEL')),

    /**
     * The queues to be consumed by the consumer command without arguments.
     */
    'consumer-queues' => [
        // 'payment-received.notifications' => \App\Listeners\PaymentReceivedNotificationsListener::class,
    ],

    'connections' => [
        'rabbitmq' => [
            'host' => env('AMQP_HOST', 'localhost'),
            'port' => env('AMQP_PORT', 5672),
            'api-port' => env('AMQP_API_PORT', 15672),
            'user' => env('AMQP_USER', 'guest'),
            'password' => env('AMQP_PASSWORD', ''),
            'vhost' => env('AMQP_VHOST', '/'),
        ],
    ],
];
