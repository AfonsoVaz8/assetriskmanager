<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class SafeHostDiscoveryProbe
{
    /**
     * Perform a conservative multi-signal probe against a target host.
     *
     * @return array{
     *     status:string,
     *     open_ports:array<int>,
     *     error:?string,
     *     signals:array{
     *         method:string,
     *         tcp:array<string,mixed>,
     *         icmp:array<string,mixed>
     *     }
     * }
     */
    public function probe(
        string $ipAddress,
        array $ports,
        float $timeoutSeconds = 1.0,
        string $method = 'tcp_icmp',
        int $icmpTimeoutSeconds = 1
    ): array
    {
        $method = in_array($method, ['tcp_only', 'icmp_only', 'tcp_icmp'], true)
            ? $method
            : 'tcp_icmp';

        $tcp = $method === 'icmp_only'
            ? $this->emptyTcpSignals()
            : $this->probeTcp($ipAddress, $ports, $timeoutSeconds);

        $icmp = $method === 'tcp_only'
            ? $this->emptyIcmpSignals()
            : $this->probeIcmp($ipAddress, $icmpTimeoutSeconds);

        return [
            'status' => $this->resolveStatus($method, $tcp, $icmp),
            'open_ports' => array_values(array_unique($tcp['open_ports'] ?? [])),
            'error' => $this->resolveError($method, $icmp),
            'signals' => [
                'method' => $method,
                'tcp' => $tcp,
                'icmp' => $icmp,
            ],
        ];
    }

    /**
     * @return array{attempted:bool,open_ports:array<int>,reachable:bool,filtered:bool,observations:array<int,array<string,mixed>>}
     */
    private function probeTcp(string $ipAddress, array $ports, float $timeoutSeconds): array
    {
        $openPorts = [];
        $reachable = false;
        $filtered = false;
        $observations = [];

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
                $reachable = true;
                $observations[] = [
                    'port' => $port,
                    'result' => 'open',
                    'message' => null,
                ];
                fclose($connection);
                continue;
            }

            $errorMessage = trim((string) ($errorMessage ?? ''));
            $normalizedMessage = strtolower($errorMessage);

            if (str_contains($normalizedMessage, 'refused')) {
                $reachable = true;
                $observations[] = [
                    'port' => $port,
                    'result' => 'closed',
                    'message' => $errorMessage,
                ];
                continue;
            }

            if (
                str_contains($normalizedMessage, 'timed out')
                || str_contains($normalizedMessage, 'timeout')
                || str_contains($normalizedMessage, 'operation now in progress')
            ) {
                $filtered = true;
                $observations[] = [
                    'port' => $port,
                    'result' => 'filtered',
                    'message' => $errorMessage,
                ];
                continue;
            }

            $observations[] = [
                'port' => $port,
                'result' => 'no_response',
                'message' => $errorMessage !== '' ? $errorMessage : null,
            ];
        }

        return [
            'attempted' => true,
            'open_ports' => array_values(array_unique($openPorts)),
            'reachable' => $reachable,
            'filtered' => $filtered,
            'observations' => $observations,
        ];
    }

    /**
     * @return array{attempted:bool,state:string,message:?string}
     */
    private function probeIcmp(string $ipAddress, int $timeoutSeconds): array
    {
        try {
            $timeoutSeconds = max(1, min(5, $timeoutSeconds));
            $command = PHP_OS_FAMILY === 'Windows'
                ? [
                    'ping',
                    '-n',
                    '1',
                    '-w',
                    (string) ($timeoutSeconds * 1000),
                    $ipAddress,
                ]
                : [
                    'ping',
                    '-c',
                    '1',
                    '-W',
                    (string) $timeoutSeconds,
                    $ipAddress,
                ];

            $result = Process::timeout($timeoutSeconds + 2)->run($command);
        } catch (\Throwable $exception) {
            return [
                'attempted' => true,
                'state' => 'unsupported',
                'message' => $exception->getMessage(),
            ];
        }

        $output = trim($result->output().' '.$result->errorOutput());
        $normalized = strtolower($output);

        if ($result->successful()) {
            return [
                'attempted' => true,
                'state' => 'reply',
                'message' => $output !== '' ? $output : null,
            ];
        }

        if (str_contains($normalized, 'destination host unreachable')) {
            return [
                'attempted' => true,
                'state' => 'unreachable',
                'message' => $output !== '' ? $output : null,
            ];
        }

        if (
            str_contains($normalized, '0 received')
            || str_contains($normalized, '100% packet loss')
            || str_contains($normalized, 'request timeout')
            || str_contains($normalized, 'timed out')
        ) {
            return [
                'attempted' => true,
                'state' => 'no_reply',
                'message' => $output !== '' ? $output : null,
            ];
        }

        return [
            'attempted' => true,
            'state' => 'unknown',
            'message' => $output !== '' ? $output : null,
        ];
    }

    /**
     * @param array<string,mixed> $tcp
     * @param array<string,mixed> $icmp
     */
    private function resolveStatus(string $method, array $tcp, array $icmp): string
    {
        if (($tcp['reachable'] ?? false) || ($icmp['state'] ?? null) === 'reply') {
            return 'active';
        }

        if ($method !== 'tcp_only' && ($icmp['state'] ?? null) === 'unreachable') {
            return 'inactive';
        }

        if (($tcp['filtered'] ?? false) || ($icmp['state'] ?? null) === 'no_reply') {
            return 'filtered';
        }

        return 'unknown';
    }

    /**
     * @param array<string,mixed> $icmp
     */
    private function resolveError(string $method, array $icmp): ?string
    {
        if ($method !== 'tcp_only' && ($icmp['state'] ?? null) === 'unsupported') {
            return 'ICMP probing is not available in the current runtime.';
        }

        return null;
    }

    /**
     * @return array{attempted:bool,open_ports:array<int>,reachable:bool,filtered:bool,observations:array<int,array<string,mixed>>}
     */
    private function emptyTcpSignals(): array
    {
        return [
            'attempted' => false,
            'open_ports' => [],
            'reachable' => false,
            'filtered' => false,
            'observations' => [],
        ];
    }

    /**
     * @return array{attempted:bool,state:string,message:?string}
     */
    private function emptyIcmpSignals(): array
    {
        return [
            'attempted' => false,
            'state' => 'not_requested',
            'message' => null,
        ];
    }
}
