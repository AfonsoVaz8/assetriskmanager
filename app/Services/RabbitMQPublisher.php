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
        // Temporarily hardcoded RabbitMQ connection settings for local testing inside Docker.
        // Replace with config/env values after verification.
        $host = 'host.docker.internal';
        $port = 5672;
        $user = 'admin';
        $pass = 'admin123';
        $vhost = '/';
        $this->queue = 'asset.sync';

        // Debug: print connection parameters (avoid printing passwords in production)
        error_log(sprintf('RabbitMQPublisher: connecting host=%s port=%d user=%s vhost=%s queue=%s', $host, $port, $user, $vhost, $this->queue));

        try {
            error_log('RabbitMQPublisher: attempting AMQP connection...');
            $this->connection = new AMQPStreamConnection($host, (int)$port, $user, $pass, $vhost);
            $this->channel = $this->connection->channel();
            $this->channel->queue_declare($this->queue, false, true, false, false);
            error_log('RabbitMQPublisher: connection and queue declare OK');
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
            error_log(sprintf('RabbitMQPublisher: channel not available, skipping publish asset_id=%d event=%s', $assetId, $event));
            return false;
        }

        $payload = json_encode(['asset_id' => $assetId, 'event' => $event]);
        $msg = new AMQPMessage($payload, ['content_type' => 'application/json', 'delivery_mode' => 2]);

        try {
            $this->channel->basic_publish($msg, '', $this->queue);
            Log::info('Published asset event to RabbitMQ', ['asset_id' => $assetId, 'event' => $event, 'queue' => $this->queue]);
            error_log(sprintf('RabbitMQPublisher: published asset_id=%d event=%s queue=%s', $assetId, $event, $this->queue));
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
