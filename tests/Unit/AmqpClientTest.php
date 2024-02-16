<?php

use Medeiroz\AmqpToolkit\AmqpClient;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;

uses()->group('amqp')->beforeEach(function () {
    $this->settings = [
        'schemas' => '/app/amqp-toolkit-schemas',
        'table_name' => 'amqp_schemas',
        'max-attempts' => 10,
        'heartbeat' => 30,
        'keepalive' => true,
        'connection' => 'rabbitmq',
        'logging-channel' => 'stderr',
        'connections' => [
            'rabbitmq' => [
                'host' => 'api-ms-indicacao-v2-rabbitmq',
                'port' => '5672',
                'api-port' => '15672',
                'user' => 'user',
                'password' => 'pass',
                'vhost' => '/',
            ],
        ],
    ];

    $this->mockLogger = mock(LoggerInterface::class);
    $this->mockAmqpConnectionFactory = mock(AMQPConnectionFactory::class);
});

it('can connect to AMQP server', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->connect();

    expect($amqpClient->getConnection())->toBeInstanceOf(AMQPStreamConnection::class);
    expect($amqpClient->getChannel())->toBeInstanceOf(AMQPChannel::class);
});

it('can get setting', function () {
    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    expect($amqpClient->getSetting('host'))->toBe('api-ms-indicacao-v2-rabbitmq');
    expect($amqpClient->getSetting('port'))->toBe('5672');
    expect($amqpClient->getSetting('api-port'))->toBe('15672');
    expect($amqpClient->getSetting('user'))->toBe('user');
    expect($amqpClient->getSetting('password'))->toBe('pass');
    expect($amqpClient->getSetting('vhost'))->toBe('/');
});

it('can disconnect from AMQP server', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Disconnecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server disconnected');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockConnection->shouldReceive('close')
        ->once();

    $mockChannel->shouldReceive('close')
        ->once();

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->connect();
    $amqpClient->disconnect();

    expect($amqpClient->getConnection())->toBeNull();
});

it('can reconnect to AMQP server', function () {

    $this->mockLogger->shouldReceive('debug')->with('Reconnecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('Disconnecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server disconnected');
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server reconnected');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->twice()
        ->andReturn($mockChannel);

    $mockConnection->shouldReceive('close')
        ->once();

    $mockChannel->shouldReceive('close')
        ->once();

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->twice()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->connect();
    $amqpClient->reconnect();

    expect($amqpClient->getConnection())->toBeInstanceOf(AMQPStreamConnection::class);
    expect($amqpClient->getChannel())->toBeInstanceOf(AMQPChannel::class);
});

it('can get connection', function () {
    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    expect($amqpClient->getConnection())->toBeNull();
});

it('can get channel', function () {
    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    expect($amqpClient->getChannel())->toBeNull();
});

it('can create queue', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Creating queue: test-queue');
    $this->mockLogger->shouldReceive('debug')->with('Queue created: test-queue');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('queue_declare')
        ->once()
        ->with('test-queue', false, true, false, false, false, $this->isInstanceOf(AMQPTable::class));

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->createQueue('test-queue');
});

it('can delete queue', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Deleting queue: test-queue');
    $this->mockLogger->shouldReceive('debug')->with('Queue deleted: test-queue');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('queue_delete')
        ->once()
        ->with('test-queue');

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->deleteQueue('test-queue');
});

it('can create exchange', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Creating exchange: test-exchange');
    $this->mockLogger->shouldReceive('debug')->with('Exchange created: test-exchange');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('exchange_declare')
        ->once()
        ->with('test-exchange', 'fanout', false, true, false);

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->createExchange('test-exchange');
});

it('can delete exchange', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Deleting exchange: test-exchange');
    $this->mockLogger->shouldReceive('debug')->with('Exchange deleted: test-exchange');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('exchange_delete')
        ->once()
        ->with('test-exchange');

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->deleteExchange('test-exchange');
});

it('can bind queue to exchange', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Binding queue test-queue to exchange test-exchange');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('queue_bind')
        ->once()
        ->with('test-queue', 'test-exchange', '');

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->bind('test-queue', 'test-exchange');
});

it('can unbind queue from exchange', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Unbinding queue test-queue to exchange test-exchange');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('queue_unbind')
        ->once()
        ->with('test-queue', 'test-exchange', '');

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->unbind('test-queue', 'test-exchange');
});

it('can publish message to exchange', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Message published to exchange test-exchange or queue {"say": "hello"}');

    $message = new AMQPMessage('{"say": "hello"}');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('basic_publish')
        ->once()
        ->with($message, 'test-exchange', '');

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->publish($message, 'test-exchange');
});

it('can publish message to queue', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Message published to exchange  or queue test-queue{"say": "hello"}');

    $message = new AMQPMessage('{"say": "hello"}');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('basic_publish')
        ->once()
        ->with($message, '', 'test-queue');

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->publish($message, '', 'test-queue');
});

it('can consume message from queue', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Consuming queue: test-queue');
    $this->mockLogger->shouldReceive('debug')->with('Queue consume finished: test-queue');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $mockChannel->shouldReceive('basic_qos')
        ->once()
        ->with(0, 1, false);

    $mockChannel->shouldReceive('basic_consume')
        ->once()
        ->with('test-queue', '', false, false, false, false, $this->isType('callable'));

    $mockChannel->shouldReceive('is_consuming')
        ->twice()
        ->with()
        ->andReturns(true, false);

    $mockChannel->shouldReceive('wait')
        ->once()
        ->with();

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $callback = function ($message) {
        return $message;
    };

    $amqpClient->consume('test-queue', $callback);
});

it('can accept message', function () {
    $this->mockLogger->shouldReceive('debug')->with('Message accepted: {"say": "hello"}');

    $message = mock(AMQPMessage::class);

    $message->shouldReceive('getBody')
        ->once()
        ->with()
        ->andReturn('{"say": "hello"}');

    $message->shouldReceive('ack')
        ->once()
        ->with();

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->accept($message);
});

it('can reject message - send to retry queue', function () {
    $this->mockLogger->shouldReceive('debug')->with('Message rejected 1 attempts: message of error');

    $message = mock(AMQPMessage::class);

    $message->shouldReceive('get->getNativeData')
        ->once()
        ->andReturn(['x-death' => [['count' => 0]]]);

    $message->shouldReceive('nack')
        ->once()
        ->with(false, false);

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->reject($message, new Exception('message of error'));
});

it('can reject message - send to dead letter queue', function () {
    $this->mockLogger->shouldReceive('debug')->with('Connecting AMQP server...');
    $this->mockLogger->shouldReceive('debug')->with('AMQP server connected');
    $this->mockLogger->shouldReceive('debug')->with('Message rejected 11 attempts: message of error');
    $this->mockLogger->shouldReceive('debug')->with('Message forward to DLQ: {"say": "hello"}');

    $mockConnection = mock(AMQPStreamConnection::class);
    $mockChannel = mock(AMQPChannel::class);

    $mockConnection->shouldReceive('channel')
        ->once()
        ->andReturn($mockChannel);

    $this->mockAmqpConnectionFactory->shouldReceive('create')
        ->once()
        ->andReturn($mockConnection);

    $headers = new AMQPTable();
    $headers->set('x-death', [['count' => 10]]);

    $message = mock(AMQPMessage::class);

    $message->shouldReceive('get')
        ->once()
        ->with('application_headers')
        ->andReturn($headers);

    $message->shouldReceive('get')
        ->once()
        ->with('routing_key')
        ->andReturn('test-queue');

    $message->shouldReceive('ack')
        ->once()
        ->with();

    $message->shouldReceive('getBody')
        ->once()
        ->with()
        ->andReturn('{"say": "hello"}');

    $mockChannel->shouldReceive('basic_publish')
        ->once()
        ->with($message, '', 'test-queue.dlq');

    $amqpClient = new AmqpClient(
        $this->mockAmqpConnectionFactory,
        $this->mockLogger,
        $this->settings,
    );

    $amqpClient->connect();

    $amqpClient->reject($message, new Exception('message of error'));
});
