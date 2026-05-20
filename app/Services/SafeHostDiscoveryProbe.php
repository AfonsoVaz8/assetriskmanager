<?php

namespace App\Services;

class SafeHostDiscoveryProbe
{
    /**
     * Perform a conservative TCP connect probe against a small port set.
     *
     * @return array{status:string,open_ports:array<int>,error:?string}
     */
    public function probe(string $ipAddress, array $ports, float $timeoutSeconds = 1.0): array
    {
        $openPorts = [];

        foreach ($ports as $port) {
            $port = (int) $port;

            if ($port <= 0 || $port > 65535) {
                continue;
            }

            $connection = @stream_socket_client(
                "tcp://{$ipAddress}:{$port}",
                $errorCode,
                $errorMessage,
                $timeoutSeconds,
                STREAM_CLIENT_CONNECT
            );

            if (is_resource($connection)) {
                $openPorts[] = $port;
                fclose($connection);
            }
        }

        return [
            'status' => !empty($openPorts) ? 'active' : 'inactive',
            'open_ports' => array_values(array_unique($openPorts)),
            'error' => null,
        ];
    }
}
