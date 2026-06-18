<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher
{
    protected $connection;
    protected $channel;
    protected $queue;

    public function __construct()
    {
        $this->queue = config('rabbitmq.queue', 'asset.sync');

        $host = config('rabbitmq.host', 'localhost');
        $port = config('rabbitmq.port', 5672);
        $user = config('rabbitmq.user', 'guest');
        $pass = config('rabbitmq.password', 'guest');
        $vhost = config('rabbitmq.vhost', '/');

        try {
            $this->connection = new AMQPStreamConnection($host, (int)$port, $user, $pass, $vhost);
            $this->channel = $this->connection->channel();
            $this->channel->queue_declare($this->queue, false, true, false, false);
        } catch (\Exception $e) {
            Log::error('RabbitMQ connection failed: ' . $e->getMessage());
            error_log('RabbitMQPublisher: connection failed: ' . $e->getMessage());
            $this->connection = null;
            $this->channel = null;
        }
    }

    public function publishAssetEvent(int $assetId, string $event = 'updated'): bool
    {
        if (!$this->channel) {
            Log::warning('RabbitMQ channel not available; skipping publish', ['asset_id' => $assetId, 'event' => $event]);
            return false;
        }

        $payload = json_encode(['asset_id' => $assetId, 'event' => $event]);
        $msg = new AMQPMessage($payload, ['content_type' => 'application/json', 'delivery_mode' => 2]);

        try {
            $this->channel->basic_publish($msg, '', $this->queue);
            Log::info('Published asset event to RabbitMQ', ['asset_id' => $assetId, 'event' => $event, 'queue' => $this->queue]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to publish asset event to RabbitMQ: ' . $e->getMessage());
            error_log('RabbitMQPublisher: publish failed: ' . $e->getMessage());
            return false;
        } finally {
            if ($this->channel) {
                try { $this->channel->close(); } catch (\Exception $e) { }
            }
            if ($this->connection) {
                try { $this->connection->close(); } catch (\Exception $e) { }
            }
        }
    }
}
